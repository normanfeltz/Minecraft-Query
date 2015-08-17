<?php
namespace xPaw;

class MinecraftPing
{
    /*
     * Queries Minecraft server
     * Returns array on success, false on failure.
     *
     * WARNING: This method was added in snapshot 13w41a (Minecraft 1.7)
     *
     * Written by xPaw
     *
     * Website: http://xpaw.me
     * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
     *
     * ---------
     *
     * This method can be used to get server-icon.png too.
     * Something like this:
     *
     * $server = new MinecraftPing('localhost');
     * $info = $server->query();
     * echo '<img width="64" height="64" src="'.str_replace("\n", "", $info['favicon']).'">';
     *
     */

    private $socket;
    private $serveraddress;
    private $serverport;
    private $timeout;

    public function __construct($address, $port = 25565, $timeout = 2)
    {
        $this->serveraddress = $address;
        $this->serverport = (int)$port;
        $this->timeout = (int)$timeout;

        $this->connect();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if ($this->socket !== null)
        {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function connect()
    {
        $connecttimeout = $this->timeout;
        $this->socket = @fsockopen($this->serveraddress, $this->serverport, $errno, $errstr, $connecttimeout);

        if (!$this->socket)
        {
            throw new MinecraftPingException("Failed to connect or create a socket: $errno ($errstr)");
        }

        // Set Read/Write timeout
        stream_set_timeout($this->socket, $this->timeout);
    }

    public function query()
    {
        $timeStart = microtime(true); // for read timeout purposes

        // See http://wiki.vg/Protocol (Status Ping)
        $data = "\x00"; // packet ID = 0 (varint)

        $data .= "\x04"; // Protocol version (varint)
        $data .= pack('c', strlen($this->serveraddress)).$this->serveraddress; // Server (varint len + UTF-8 addr)
        $data .= pack('n', $this->serverport); // Server port (unsigned short)
        $data .= "\x01"; // Next state: status (varint)

        $data = pack('c', strlen($data)).$data; // prepend length of packet ID + data

        fwrite($this->socket, $data); // handshake
        fwrite($this->socket, "\x01\x00"); // status ping

        $length = $this->readvarint(); // full packet length

        if ($length < 10)
        {
            return FALSE;
        }

        fgetc($this->socket); // packet type, in server ping it's 0

        $length = $this->readvarint(); // string length

        $data = "";
        do
        {
            if (microtime(true) - $timeStart > $this->timeout)
            {
                throw new MinecraftPingException('Server read timed out');
            }

            $remainder = $length - strlen($data);
            $block = fread($this->socket, $remainder); // and finally the json string
            // abort if there is no progress
            if (!$block)
            {
                throw new MinecraftPingException('Server returned too few data');
            }

            $data .= $block;
        }
        while(strlen($data) < $length);

        if ($data === FALSE)
        {
            throw new MinecraftPingException( 'Server didn\'t return any data' );
        }

        $data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            if (function_exists('json_last_error_msg'))
            {
                throw new MinecraftPingException(json_last_error_msg());
            }
            else
            {
                throw new MinecraftPingException('JSON parsing failed');
            }
            return FALSE;
        }
        return $data;
    }

    public function queryOldPre17()
    {
        fwrite($this->socket, "\xFE\x01");
        $data = fread($this->socket, 512);
        $len = strlen($data);

        if ($len < 4 || $data[0] !== "\xFF")
        {
            return FALSE;
        }

        $data = substr($data, 3); // Strip packet header (kick message packet and short length)
        $data = iconv('UTF-16BE', 'UTF-8', $data);

        // Are we dealing with Minecraft 1.4+ server?
        if ($data[1] === "\xA7" && $data[2] === "\x31")
        {
            $data = explode("\x00", $data);
            return array(
                'HostName' => $data[3],
                'Players' => intval($data[4]),
                'MaxPlayers' => intval($data[5]),
                'Protocol' => intval($data[1]),
                'Version' => $data[2]
            );
        }

        $data = explode("\xA7", $data);

        return array(
            'HostName' => substr($data[0], 0, -1),
            'Players' => isset($data[1])?intval($data[1]):0,
            'MaxPlayers' => isset($data[2])?intval($Data[2]):0,
            'Protocol' => 0,
            'Version' => '1.3'
        );
    }

    public function queryBungeeCord()
    {
        fwrite($this->socket, "\xFE\x01\xFA");
        $data = fread($this->socket, 512);
        $len = strlen($data);

        if ($len < 4 || $data[0] !== "\xFF")
        {
            return FALSE;
        }

        $data = substr($data, 3); // Strip packet header (kick message packet and short length)
        $data = iconv('UTF-16BE', 'UTF-8', $data);

        // Are we dealing with Minecraft 1.4+ server?
        if ($data[1] === "\xA7" && $data[2] === "\x31")
        {
            $data = explode("\x00", $data);
            return array(
                'HostName' => $data[3],
                'Players' => intval($data[4]),
                'MaxPlayers' => intval($data[5]),
                'Protocol' => intval($data[1]),
                'Version' => $data[2]
            );
        }

        $data = explode("\xA7", $data);

        return array(
            'HostName' => substr($data[0], 0, -1),
            'Players' => isset($data[1])?intval($data[1]):0,
            'MaxPlayers' => isset($data[2])?intval($Data[2]):0,
            'Protocol' => 0,
            'Version' => '1.3'
        );
    }

    private function readVarInt()
    {
        $i = 0;
        $j = 0;

        while(true)
        {
            $k = @fgetc($this->socket);

            if ($k === FALSE)
            {
                return 0;
            }

            $k = ord($k);

            $i |= ($k & 0x7F) << $j++ * 7;

            if ($j > 5)
            {
                throw new MinecraftPingException('VarInt too big');
            }

            if (($k & 0x80) != 128)
            {
                break;
            }
        }
        return $i;
    }
}
