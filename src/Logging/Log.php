<?php

namespace Utopia\Logging;

use Exception;
use Utopia\Logging\Log\Breadcrumb;
use Utopia\Logging\Log\User;

class Log
{
    /**
     * @var float (required, set by default to microtime())
     */
    protected float $timestamp;

    /**
     * @var string (required can be 'log', 'error' or 'warning')
     */
    protected string $type;

    /**
     * @var string (required)
     */
    protected string $message;

    /**
     * @var string (required)
     */
    protected string $version;

    /**
     * @var string (required)
     */
    protected string $environment;

    /**
     * @var string (required)
     */
    protected string $action;

    /**
     * @var array (optional)
     */
    protected array $tags;

    /**
     * @var array (optional)
     */
    protected array $extra;

    /**
     * @var string (optional)
     */
    protected string $logger;

    /**
     * @var string (optional)
     */
    protected string $server;

    /**
     * @var User (optional)
     */
    protected User $user;

    /**
     * @var Breadcrumb[] (optional)
     */
    protected array $breadcrumbs;

    /**
     * Log constructor.
     */
    public function __construct()
    {
        $this->timestamp = \microtime(true);
    }

    /**
     * Set a type
     *
     * @param string $type (required, can be 'log', 'error' or 'warning')
     * @return void
     * @throws Exception
     */
    public function setType(string $type): void {
        switch ($type) {
              case "log":
              case "error":
              case "warning": break;
              default: throw new Exception("Unsupported log type. Must be one of 'log', 'warning' or 'error'");
        }

        $this->type = $type;
    }

    /**
     * Get the type of this log
     *
     * @return string (can be 'log', 'error' or 'warning')
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Set timestamp in seconds when log occurred
     *
     * @param float $timestamp (required)
     * @return void
     */
    public function setTimestamp(float $timestamp): void {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp in seconds when log occurred
     *
     * @return float
     */
    public function getTimestamp(): float {
        return $this->timestamp;
    }

    /**
     * Set main message
     *
     * @param string $message (required, for example 'Collection abcd1234 not found')
     * @return void
     */
    public function setMessage(string $message): void {
        $this->message = $message;
    }

    /**
     * Get main message
     *
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * Set a custom logger for easier categorizing
     *
     * @param string $logger (required, for example 'api')
     * @return void
     */
    public function setLogger(string $logger): void {
        $this->logger = $logger;
    }

    /**
     * Set a custom logger
     *
     * @return string
     */
    public function getLogger(): string {
        return $this->logger;
    }

    /**
     * Set an action to describe what caused this log
     *
     * @param string $action (required, for example 'databaseController.deleteDocument' or 'functionsWorker.executeFunction')
     * @return void
     */
    public function setAction(string $action): void {
        $this->action = $action;
    }

    /**
     * Get an action
     *
     * @return string
     */
    public function getAction(): string {
        return $this->action;
    }

    /**
     * Set identificator of server where log happened
     *
     * @param string $server (required, for example 'digitalocean-us-005')
     * @return void
     */
    public function setServer(string $server): void {
        $this->server = $server;
    }

    /**
     * Get identificator of server where log happened
     *
     * @return string
     */
    public function getServer(): string {
        return $this->server;
    }

    /**
     * Set version of application for easier bug hunting
     *
     * @param string $version (required, for example '0.11.2')
     * @return void
     */
    public function setVersion(string $version): void {
        $this->version = $version;
    }

    /**
     * Get version of application
     *
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }

    /**
     * Set version of application for easier bug hunting
     *
     * @param string $environment (required, can be 'production' or 'staging')
     * @return void
     */
    public function setEnvironment(string $environment): void {
        if($environment !== "production" && $environment !== "staging") {
            throw new Exception('Unsupported environment of log');
        }

        $this->environment = $environment;
    }

    /**
     * Get version of application
     *
     * @return string
     */
    public function getEnvironment(): string {
        return $this->environment;
    }

    /**
     * Set tags (labels)
     *
     * @param array $tags (required, for example ['theme' => 'dark', 'sdk' => 'javascript'])
     * @return void
     */
    public function setTags(array $tags): void {
        $this->tags = $tags;
    }

    /**
     * Get tags
     *
     * @return array
     */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     * Set extra metadata of log
     *
     * @param array $extra (required, for example ['theme' => 'dark', 'sdk' => 'javascript'])
     * @return void
     */
    public function setExtra(array $extra): void {
        $this->extra = $extra;
    }

    /**
     * Get extra metadata
     *
     * @return array
     */
    public function getExtra(): array {
        return $this->extra;
    }

    /**
     * Set user who caused the log
     *
     * @param User $user
     * @return void
     */
    public function setUser($user): void {
        $this->user = $user;
    }

    /**
     * Get user who caused the log
     *
     * @return User
     */
    public function getUser(): User {
        return $this->user;
    }

    /**
     * Set reproduction steps
     *
     * @param array $breadcrumbs
     * @return void
     */
    public function setBreadcrumbs($breadcrumbs): void {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Get reproduction steps
     *
     * @return Breadcrumb[]
     */
    public function getBreadcrumbs() {
        return $this->breadcrumbs;
    }
}