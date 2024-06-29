<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage TCPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization;
/**
 * @package PHPClassCollection
 * @subpackage TCPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 5.99.1
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
	protected string $host;
	/**
	 * The port of the server.
	 * @var int
	 */
	protected int $port;
	/**
	 * Determins whether to use SSL or not.
	 * @var bool
	 */
	protected bool $ssl;
	/**
	 * Determins wether to verify peer SSL/TLS certificates or not.
	 * @var boolean
	 */
	protected bool $peerVerification = true;
	/**
	 * Determins wether self-signed certificates will be accepted or not.
	 * @var boolean
	 */
	protected bool $allowSelfSigned = false;
	/**
	 * The amount of time (in seconds) the class will wait for a connection to be established.
	 * @var float
	 */
	protected float $connectionTimeout = 10;
	/**
	 * The amount of time (in seconds) the class will wait for a response to a request.
	 * @var float
	 */
	protected float $streamTimeout = 5;
	/**
	 * Decides whether or not the class should wait for the stream to respond when reading from it.
	 * @var bool
	 */
	protected bool $streamBlocking = true;

	/**
	 * Apply the set peer verification policy.
	 * @return bool
	 */
	private function applyPeerVerification(): bool
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		if (!stream_context_set_option($this->connection, 'ssl', 'verify_peer', $this->peerVerification))
		{
			return false;
		}

		return stream_context_set_option($this->connection, 'ssl', 'verify_peer_name', $this->peerVerification);
	}

	/**
	 * Apply the set certificate signature check policy.
	 * @return bool
	 * @throws \Exception
	 */
	private function applySignatureCheck(): bool
	{
		if (this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		return stream_context_set_option($this->connection, 'ssl', 'allow_self_signed', $this->allowSelfSigned);
	}

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
		$contextOptions = array(
			'ssl'	=> array(
				'verify_peer'		=> $this->peerVerification,
				'verify_peer_name'	=> $this->peerVerification,
				'allow_self_signed'	=> $this->allowSelfSigned
			)
		);
		$errNo = 0;
		$errMsg = '';
		$context = stream_context_create($contextOptions);

		if ($this->ssl === true)
		{
			$this->connection = stream_socket_client('ssl://'.$this->host.':'.$this->port, $errNo, $errMsg, $this->connectionTimeout, STREAM_CLIENT_CONNECT, $context);
		}
		else
		{
			$this->connection = stream_socket_client($this->host.':'.$this->port, $errNo, $errMsg, $this->connectionTimeout, STREAM_CLIENT_CONNECT, $context);
		}

		if ($this->connected() === false)
		{
			throw new \Exception('Cannot establish connection. Error: '.$errMsg.' ('.$errNo.')');
		}

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

	/**
	 * Disconnect from the server.
	 * @return void
	 */
	public function disconnect(): void
	{
		if ($this->connected() === true)
		{
			//fclose($this->connection);
			if (!stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR))
			{
				throw new \Exception('Cannot close connection.');
			}

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

	public function setPeerVerification(bool $peerVerification): void
	{
		$this->peerVerification = $peerVerification;

		if ($this->connected() === true)
		{
			$set = $this->applyPeerVerification();

			if ($set === false)
			{
				throw new \Exception('Failed to set peer verification.');
			}
		}
	}

	public function setAllowSelfSigned(bool $allowSelfSigned): void
	{
		$this->allowSelfSigned = $allowSelfSigned;

		if ($this->connected() === true)
		{
			$set = $this->applySignatureCheck();

			if ($set === false)
			{
				throw new \Exception('Failed to set certificate signature check.');
			}
		}
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
			$set = $this->applyStreamTimeout();

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
			$set = $this->applyStreamBlocking();

			if ($set === false)
			{
				throw new \Exception('Failed to set stream blocking mode.');
			}
		}
	}

	public function enableEncryption(bool $enabled, ?int $crypto_method = null): bool
	{
		if ($this->connected() === false)
		{
			throw new \Exception('Not connected');
		}

		$result = stream_socket_enable_crypto($this->connection, $enabled, $crypto_method);

		if ($result === 0)
		{
			return false;
		}

		return $result;
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

		$response = stream_socket_recvfrom($this->connection, $bytes);

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

		stream_socket_sendto($this->connection, $data);
	}

	/**
	 * Write a line of data to the stream.
	 * @param string $data
	 * @return void
	 * @throws \Exception
	 */
	public function writeLine(string $data, string $eol = "\r\n"): void
	{
		$this->write($data.$eol);
	}
}
