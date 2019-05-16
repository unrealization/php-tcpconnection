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
 * @version 3.0.2
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class TCPConnection
{
	/**
	 * The connection-resource.
	 * @var resource
	 */
	private $connection = false;
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
	 * The amount of time (in seconds) the class will wait for a connection to be established.
	 * @var float
	 */
	protected $connectionTimeout = 10;
	/**
	 * The amount of time (in seconds) the class will wait for a response to a request.
	 * @var float
	 */
	protected $streamTimeout = 5;
	/**
	 * Decides whether or not the class should wait for the stream to respond when reading from it.
	 * @var bool
	 */
	protected $streamBlocking = true;

	/**
	 * Apply the set stream timeout.
	 * @return bool
	 * @throws \Exception
	 */
	private function applyStreamTimeout(): bool
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		$timeoutSeconds = floor($this->streamTimeout);
		$timeoutMilliSeconds = ($this->streamTimeout - $timeoutSeconds) * 1000;
		return stream_set_timeout($this->connection, (int)$timeoutSeconds, (int)$timeoutMilliSeconds);
	}

	/**
	 * Apply the set blocking mode.
	 * @return bool
	 * @throws \Exception
	 */
	private function applyStreamBlocking(): bool
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		return stream_set_blocking($this->connection, $this->streamBlocking);
	}

	/**
	 * Constructor
	 * @param string $host
	 * @param int $port
	 * @param bool $ssl
	 */
	public function __construct(string $host, int $port, bool $ssl = false)
	{
		$this->host = $host;
		$this->port = $port;
		$this->ssl = $ssl;
	}

	/**
	 * Connect to the server.
	 * @throws \Exception
	 */
	public function connect(): void
	{
		$errNo = 0;
		$errMsg = '';

		if ($this->ssl === true)
		{
			$this->connection = fsockopen('ssl://'.$this->host, $this->port, $errNo, $errMsg, $this->connectionTimeout);
		}
		else
		{
			$this->connection = fsockopen($this->host, $this->port, $errNo, $errMsg, $this->connectionTimeout);
		}

		if ($this->connected() === false)
		{
			throw new \Exception('Cannot establish connection.');
		}

		try
		{
			$set = $this->applyStreamTimeout();

			if ($set === false)
			{
				throw new \Exception('Failed to set stream timeout.');
			}

			$set = $this->applyStreamBlocking();

			if ($set === false)
			{
				throw new \Exception('Failed to set stream blocking mode.');
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to set up connection.', 0, $e);
		}
	}

	/**
	 * Disconnect from the server.
	 * @return void
	 */
	public function disconnect(): void
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
	 * Set the connection timeout to control how long the class will wait when trying to establish a new connection.
	 * @param float $timeout
	 */
	public function setConnectionTimeout(float $timeout): void
	{
		$this->connectionTimeout = $timeout;
	}

	/**
	 * Set the stream timeout to control how long the class will wait for a response to a request.
	 * @param float $timeout
	 */
	public function setStreamTimeout(float $timeout): void
	{
		$this->streamTimeout = $timeout;

		if ($this->connected() === true)
		{
			try
			{
				$set = $this->applyStreamTimeout();
			}
			catch (\Exception $e)
			{
				throw new \Exception('Failed to set stream timeout.', 0, $e);
			}

			if ($set === false)
			{
				throw new \Exception('Failed to set stream timeout.');
			}
		}
	}

	/**
	 * Set the stream's blocking mode to control wether or not the class will wait for a response to a request.
	 * @param bool $mode
	 * @throws \Exception
	 */
	public function setStreamBlocking(bool $mode): void
	{
		$this->streamBlocking = $mode;

		if ($this->connected() === true)
		{
			try
			{
				$set = $this->applyStreamBlocking();
			}
			catch (\Exception $e)
			{
				throw new \Exception('Failed to set stream blocking mode.', 0, $e);
			}

			if ($set === false)
			{
				throw new \Exception('Failed to set stream blocking mode.');
			}
		}
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

		$response = fread($this->connection, $bytes);

		if ($response === false)
		{
			throw new \Exception('Nothing to read');
		}

		return $response;
	}

	/**
	 * Write data to the stream.
	 * @param string $data
	 * @return void
	 * @throws \Exception
	 */
	public function write(string $data): void
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
	 * @return void
	 * @throws \Exception
	 */
	public function writeLine(string $data): void
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