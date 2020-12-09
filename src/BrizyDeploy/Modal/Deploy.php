<?php

namespace BrizyDeploy\Modal;

class Deploy
{
    /**
     * @var boolean
     */
    protected $execute;

    /**
     * @var int
     */
    protected $deploy_timestamp;

    /**
     * @var boolean
     */
    protected $update;

    /**
     * @var int
     */
    protected $zip_info_timestamp;

    /**
     * Deploy constructor.
     * @param $execute
     * @param $update
     */
    public function __construct($execute, $update)
    {
        $this->execute = $execute;
        $this->update = $update;
    }

    static public function getInstance()
    {
        return new Deploy(true, false);
    }

    /**
     * @return boolean
     */
    public function getExecute()
    {
        return $this->execute;
    }

    /**
     * @param $execute
     * @return $this
     */
    public function setExecute($execute)
    {
        $this->execute = $execute;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * @param $update
     * @return $this
     */
    public function setUpdate($update)
    {
        $this->update = $update;

        return $this;
    }

    /**
     * @return int
     */
    public function getDeployTimestamp()
    {
        return $this->deploy_timestamp;
    }

    /**
     * @param $deploy_timestamp
     * @return $this
     */
    public function setDeployTimestamp($deploy_timestamp)
    {
        $this->deploy_timestamp = $deploy_timestamp;

        return $this;
    }

    /**
     * @return int
     */
    public function getZipInfoTimestamp()
    {
        return $this->zip_info_timestamp;
    }

    /**
     * @param $zip_info_timestamp
     * @return $this
     */
    public function setZipInfoTimestamp($zip_info_timestamp)
    {
        $this->zip_info_timestamp = $zip_info_timestamp;

        return $this;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->execute,
            $this->update
        ]);
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list(
            $this->execute,
            $this->update
            ) = unserialize($serialized);
    }
}