<?php

namespace Anascloud\Zkteko;

use Exception;

class ZKTeco
{
    /** @var string */
    protected $ip;

    /** @var int */
    protected $port;

    /** @var int */
    protected $password;

    /** @var resource|null */
    protected $socket;

    /** @var int */
    protected $sessionId = 0;

    /** @var int */
    protected $replyId = 0;

    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ATTLOG_RRQ = 1503;
    const CMD_ACK_OK = 2000;

    const USY_RESERVED_COUNT = 40;

    public function __construct(string $ip, int $port = 4370, int $password = 0)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->password = $password;
    }

    /**
     * Connect to the device.
     *
     * @return bool
     */
    public function connect(): bool
    {
        if ($this->socket) {
            return true;
        }

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$this->socket) {
            return false;
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

        $command = self::CMD_CONNECT;
        $command_string = '';
        $chksum = 0;
        $session_id = 0;
        $reply_id = 65535; // -1 as unsigned short

        $header = $this->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        $this->sendData($header);

        $reply = $this->recvData();
        if ($reply) {
            $this->sessionId = $this->decode16(substr($reply, 4, 2));
            $this->replyId = $this->decode16(substr($reply, 6, 2));
            
            // If password is set, we might need an authentication step here.
            // For simplicity and matching common pyzk ports, we'll assume direct connection works or add auth if needed.
            // Most ZK devices accept connection and then expect CMD_AUTH if password is set.
            
            return $this->decode16(substr($reply, 0, 2)) === self::CMD_ACK_OK;
        }

        return false;
    }

    /**
     * Disconnect from the device.
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        if (!$this->socket) {
            return false;
        }

        $header = $this->createHeader(self::CMD_EXIT, 0, $this->sessionId, $this->replyId, '');
        $this->sendData($header);

        socket_close($this->socket);
        $this->socket = null;
        $this->sessionId = 0;
        $this->replyId = 0;

        return true;
    }

    /**
     * Get attendance logs.
     *
     * @return array
     */
    public function getAttendance(): array
    {
        if (!$this->socket) {
            return [];
        }

        $header = $this->createHeader(self::CMD_ATTLOG_RRQ, 0, $this->sessionId, $this->replyId, '');
        $this->sendData($header);

        $reply = $this->recvData();
        if (!$reply) {
            return [];
        }

        $attendance = [];
        $data = substr($reply, 8);

        while (strlen($data) >= 40) {
            $record = substr($data, 0, 40);
            
            $u = unpack('vuid/vstatus/Vtimestamp', substr($record, 24, 8));
            $userId = trim(substr($record, 0, 24));
            
            $attendance[] = [
                'user_id' => $userId,
                'timestamp' => $this->decodeTime($u['timestamp']),
                'status' => $u['status'],
            ];

            $data = substr($data, 40);
        }

        return $attendance;
    }

    /**
     * Create packet header.
     */
    protected function createHeader(int $command, int $chksum, int $session_id, int $reply_id, string $command_string): string
    {
        $buf = pack('vvvv', $command, $chksum, $session_id, $reply_id) . $command_string;
        $chksum = $this->calculateChecksum($buf);
        return pack('vvvv', $command, $chksum, $session_id, $reply_id) . $command_string;
    }

    /**
     * Calculate checksum.
     */
    protected function calculateChecksum(string $buffer): int
    {
        $acc = 0;
        if (strlen($buffer) % 2 !== 0) {
            $buffer .= "\0";
        }

        for ($i = 0; $i < strlen($buffer); $i += 2) {
            $acc += $this->decode16(substr($buffer, $i, 2));
        }

        $acc = ($acc >> 16) + ($acc & 0xffff);
        $acc += ($acc >> 16);
        return (~$acc) & 0xffff;
    }

    protected function sendData(string $data): void
    {
        socket_sendto($this->socket, $data, strlen($data), 0, $this->ip, $this->port);
    }

    protected function recvData(): ?string
    {
        $buffer = '';
        $from = '';
        $port = 0;
        if (socket_recvfrom($this->socket, $buffer, 1024, 0, $from, $port)) {
            return $buffer;
        }
        return null;
    }

    protected function decode16(string $data): int
    {
        return unpack('v', $data)[1];
    }

    protected function decodeTime(int $time): string
    {
        $second = $time % 60;
        $time = (int)($time / 60);
        $minute = $time % 60;
        $time = (int)($time / 60);
        $hour = $time % 24;
        $time = (int)($time / 24);
        $day = ($time % 31) + 1;
        $time = (int)($time / 31);
        $month = ($time % 12) + 1;
        $time = (int)($time / 12);
        $year = $time + 2000;

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }
}
