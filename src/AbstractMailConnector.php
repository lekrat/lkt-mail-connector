<?php

namespace Lkt\Connectors;

abstract class AbstractMailConnector
{
    protected string $name;
    protected string $email;
    protected string $host;
    protected string $user;
    protected string $password;
    protected string $security;
    protected string $mailingFrom;
    protected int $port = 0;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setHost(string $host): static
    {
        $this->host = $host;
        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSecurity(): string
    {
        return $this->security;
    }

    public function getMailingFrom(): string
    {
        return $this->mailingFrom;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    public function setUser(string $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function setEmail(string $mail): static
    {
        $this->email = $mail;
        return $this;
    }

    public function setSecurity(string $security): static
    {
        $this->security = $security;
        return $this;
    }

    public function setMailingFrom(string $mail): static
    {
        $this->mailingFrom = $mail;
        return $this;
    }

    public function hasUserConfig(): bool
    {
        return $this->getUser() !== '' && $this->getPassword() !== '';
    }
}