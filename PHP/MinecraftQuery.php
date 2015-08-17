<?php
namespace xPaw;

class MinecraftQuery
{
    /*
     * Class written by xPaw
     *
     * Website: http://xpaw.me
     * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
     */

    const STATISTIC = 0x00;
    const HANDSHAKE = 0x09;

    private $socket;
    private $players;
    private $info;

    public function connect($ip, $port = 25565, $timeout = 3)
    {
        if (!is_int($timeout) || $timeout < 0)
        {
            throw new \InvalidArgumentException('Timeout must be an integer.');
        }

        $this->socket = @fsockopen('udp://'.$ip, (int)$port, $errno, $errstr, $timeout);

        if ($errno || $this->socket === false)
        {
            throw new MinecraftQueryException('Could not create socket: '.$errstr);
        }

        stream_set_timeout($this->socket, $timeout);
        stream_set_blocking($this->socket, true);

        try
        {
            $challenge = $this->getChallenge();
            $this->getStatus($challenge);
        }
        catch(MinecraftQueryException $e)
        {
            fclose($this->socket);
            throw new MinecraftQueryException($e->getMessage());
        }
        fclose($this->socket);
    }

    public function getInfo()
    {
        return isset($this->info)?$this->info:false;
    }

    public function getPlayers()
    {
        return isset($this->players)?$this->players:false;
    }

    private function getChallenge()
    {
        $data = $this->writeData(self::HANDSHAKE);

        if ($data === false)
        {
            throw new MinecraftQueryException('Failed to receive challenge.');
        }
        return pack('N', $data);
    }

    private function getStatus($challenge)
    {
        $data = $this->writeData(self::STATISTIC, $challenge.pack('c*', 0x00, 0x00, 0x00, 0x00));

        if (!$data)
        {
            throw new MinecraftQueryException('Failed to receive status.');
        }

        $last = '';
        $info = array();

        $data = substr($data, 11); // splitnum + 2 int
        $data = explode("\x00\x00\x01player_\x00\x00", $data);

        if (count($data) !== 2)
        {
            throw new MinecraftQueryException('Failed to parse server\'s response.');
        }

        $players = substr($data[1], 0, -2);
        $data = explode("\x00", $data[0]);

        // Array with known keys in order to validate the result
        // It can happen that server sends custom strings containing bad things (who can know!)
        $keys = array(
            'hostname' => 'HostName',
            'gametype' => 'GameType',
            'version' => 'Version',
            'plugins' => 'Plugins',
            'map' => 'Map',
            'numplayers' => 'Players',
            'maxplayers' => 'MaxPlayers',
            'hostport' => 'HostPort',
            'hostip' => 'HostIp',
            'game_id' => 'GameName'
        );

        foreach($data as $key => $value)
        {
            if (~$key & 1)
            {
                if (!array_key_exists($value, $keys))
                {
                    $last = false;
                    continue;
                }

                $last = $keys[$value];
                $info[$last] = '';
            }
            elseif ($last != false)
            {
                $info[$last] = $value;
            }
        }

        // Ints
        $info['Players'] = intval($info['Players']);
        $info['MaxPlayers'] = intval($info['MaxPlayers']);
        $info['HostPort'] = intval($info['HostPort']);

        // Parse "plugins", if any
        if ($info['Plugins'])
        {
            $data = explode(": ", $info['Plugins'], 2);

            $info['RawPlugins'] = $info['Plugins'];
            $info['Software'] = $data[0];

            if (count($data) == 2)
            {
                $info['Plugins'] = explode("; ", $data[1]);
            }
        }
        else
        {
            $info['Software'] = 'Vanilla';
        }

        $this->info = $info;

        if ($players)
        {
            $this->players = explode("\x00", $players);
        }
    }

    private function writeData($command, $append = "")
    {
        $command = pack('c*', 0xFE, 0xFD, $command, 0x01, 0x02, 0x03, 0x04).$append;
        $length  = strlen($command);

        if ($length !== fwrite($this->socket, $command, $length))
        {
            throw new MinecraftQueryException("Failed to write on socket.");
        }

        $data = fread($this->socket, 4096);

        if ($data === false)
        {
            throw new MinecraftQueryException("Failed to read from socket.");
        }

        if (strlen($data) < 5 || $data[0] != $command[2])
        {
            return false;
        }

        return substr($data, 5);
    }
}
