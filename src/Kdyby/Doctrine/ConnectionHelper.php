<?php declare(strict_types = 1);

namespace Kdyby\Doctrine;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Helper\Helper;

class ConnectionHelper extends Helper
{
    /**
     * The Doctrine database Connection.
     *
     * @var Connection
     */
    protected $_connection;

    /**
     * @param Connection $connection The Doctrine database Connection.
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Retrieves the Doctrine database Connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'connection';
    }
}
