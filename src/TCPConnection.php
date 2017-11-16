<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage TCPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization\PHPClassCollection;
/**
 * @package PHPClassCollection
 * @subpackage TCPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 1.4.2
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class TCPConnection
{
	/**
	 * The connection-resource.
	 * @var resource
	 */
	private $connection;
	/**
	 * The address of the server.
	 * @var string
	 */
	protected $host;
	/**
	 * The port of the server.
	 * @var int
	 */
	protected $port;
	/**
	 * Determins whether to use SSL or not.
	 * @var bool
	 */
	protected $ssl;

	/**
	 * Constructor
	 * @param string $host
	 * @param int $port
	 * @param bool $ssl
	 */
	public function __construct(string $host, int $port, bool $ssl = false)
	{
		$this->connection = false;
		$this->host = $host;
		$this->port = $port;
		$this->ssl = $ssl;
	}

	/**
	 * Connect to the server.
	 * @return bool
	 */
	public function connect(): bool
	{
		if ($this->ssl === true)
		{
			$this->connection = fsockopen('ssl://'.$this->host, $this->port);
		}
		else
		{
			$this->connection = fsockopen($this->host, $this->port);
		}

		return $this->connected();
	}

	/**
	 * Disconnect from the server.
	 */
	public function disconnect()
	{
		if ($this->connected() === true)
		{
			fclose($this->connection);
			$this->connection = false;
		}
	}

	/**
	 * Check if the connection has been established.
	 * @return bool
	 */
	public function connected(): bool
	{
		return ($this->connection !== false);
	}

	/**
	 * Set the timeout.
	 * @param int $sec
	 * @param int $msec
	 * @return bool
	 * @throws \Exception
	 */
	public function setTimeout(int $sec, int $mSec = 0): bool
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		return stream_set_timeout($this->connection, $sec, $mSec);
	}

	/**
	 * Set the mode of stream-blocking.
	 * @param int $mode
	 * @return bool
	 * @throws \Exception
	 */
	public function setBlocking(int $mode): bool
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		return stream_set_blocking($this->connection, $mode);
	}

	/**
	 * Read all data from the stream.
	 * @return string
	 * @throws \Exception
	 */
	public function read(): string
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		$response = '';
		$continue = true;

		while ($continue == true)
		{
			$data = null;

			try
			{
				$data = $this->readLine();
			}
			catch (\Exception $e)
			{
				$continue = false;
			}

			if (!is_null($data))
			{
				$response .= $data;
			}
		}

		return $response;
	}

	/**
	 * Read a line of data from the stream.
	 * @return string
	 * @throws \Exception
	 */
	public function readLine(): string
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		$response = fgets($this->connection);

		if ($response === false)
		{
			throw new \Exception('Nothing to read');
		}

		return $response;
	}

	/**
	 * Read a number of bytes from the stream.
	 * @param int $bytes
	 * @return string
	 * @throws \Exception
	 */
	public function readBytes(int $bytes): string
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		return fread($this->connection, $bytes);
	}

	/**
	 * Write data to the stream.
	 * @param string $data
	 * @throws \Exception
	 */
	public function write(string $data)
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		fwrite($this->connection, $data);
	}

	/**
	 * Write a line of data to the stream.
	 * @param string $data
	 * @throws \Exception
	 */
	public function writeLine(string $data)
	{
		try
		{
			$this->write($data."\r\n");
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
?>