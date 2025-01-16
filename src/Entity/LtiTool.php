<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;

#[ORM\Entity(repositoryClass: "Classroom\Repository\LtiToolRepository")]
#[ORM\Table(name: "lti_tools")]
class LtiTool {
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private $id;

    #[ORM\OneToOne(targetEntity: "Classroom\Entity\Applications")]
    #[ORM\JoinColumn(name: "application_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $application;

    #[ORM\Column(name: "client_id", type: "string", length: 255, nullable: false)]
    private $clientId = '';

    #[ORM\Column(name: "deployment_id", type: "string", length: 255, nullable: false)]
    private $deploymentId = '';

    #[ORM\Column(name: "tool_url", type: "string", length: 255, nullable: false)]
    private $toolUrl = '';

    #[ORM\Column(name: "public_key_set", type: "string", length: 255, nullable: false)]
    private $publicKeySet = '';

    #[ORM\Column(name: "login_url", type: "string", length: 255, nullable: false)]
    private $loginUrl = '';

    #[ORM\Column(name: "redirection_url", type: "string", length: 255, nullable: false)]
    private $redirectionUrl = '';

    #[ORM\Column(name: "deeplink_url", type: "string", length: 255, nullable: false)]
    private $deepLinkUrl = '';

    #[ORM\Column(name: "private_key", type: "text", length: 10000, nullable: false)]
    private $privateKey = '';

    #[ORM\Column(name: "kid", type: "string", length: 255, nullable: false)]
    private $kid = '';

    // Getters and setters...

    public function getId() {
        return $this->id;
    }

    public function getApplication() {
        return $this->application;
    }

    public function setApplication($application) {
        if (!($application instanceof Applications)) {
            throw new EntityDataIntegrityException("The application has to be an instance of Applications class");
        }
        $this->application = $application;
        return $this;
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function setClientId($clientId) {
        if (!is_string($clientId)) {
            throw new EntityDataIntegrityException("The client id has to be a string value");
        }
        $this->clientId = $clientId;
        return $this;
    }

    public function getDeploymentId() {
        return $this->deploymentId;
    }

    public function setDeploymentId($deploymentId) {
        if (!is_string($deploymentId)) {
            throw new EntityDataIntegrityException("The deployment id has to be a string");
        }
        $this->deploymentId = $deploymentId;
        return $this;
    }

    public function getToolUrl() {
        return $this->toolUrl;
    }

    public function setToolUrl($toolUrl) {
        if (!is_string($toolUrl)) {
            throw new EntityDataIntegrityException("The tool url has to be a string value");
        }
        $this->toolUrl = $toolUrl;
        return $this;
    }

    public function getPublicKeySet() {
        return $this->publicKeySet;
    }

    public function setPublicKeySet($publicKeySet) {
        if (!is_string($publicKeySet)) {
            throw new EntityDataIntegrityException("The public key set has to be a string value");
        }
        $this->publicKeySet = $publicKeySet;
        return $this;
    }

    public function getLoginUrl() {
        return $this->loginUrl;
    }

    public function setLoginUrl($loginUrl) {
        if (!is_string($loginUrl)) {
            throw new EntityDataIntegrityException("The login url has to be a string value");
        }
        $this->loginUrl = $loginUrl;
        return $this;
    }

    public function getRedirectionUrl() {
        return $this->redirectionUrl;
    }

    public function setRedirectionUrl($redirectionUrl) {
        if (!is_string($redirectionUrl)) {
            throw new EntityDataIntegrityException("The redirection url has to be a string value");
        }
        $this->redirectionUrl = $redirectionUrl;
        return $this;
    }

    public function getDeepLinkUrl() {
        return $this->deepLinkUrl;
    }

    public function setDeepLinkUrl($deepLinkUrl) {
        if (!is_string($deepLinkUrl)) {
            throw new EntityDataIntegrityException("The deep link url has to be a string value");
        }
        $this->deepLinkUrl = $deepLinkUrl;
        return $this;
    }

    public function getPrivateKey() {
        return $this->privateKey;
    }

    public function setPrivateKey($privateKey) {
        if (!is_string($privateKey)) {
            throw new EntityDataIntegrityException("The private key has to be a string value");
        }
        $this->privateKey = $privateKey;
        return $this;
    }

    public function getKid() {
        return $this->kid;
    }

    public function setKid($kid) {
        if (!is_string($kid)) {
            throw new EntityDataIntegrityException("The kid has to be a string value");
        }
        $this->kid = $kid;
        return $this;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->getId(),
            'clientId' => $this->getClientId(),
            'deploymentId' => $this->getDeploymentId(),
            'toolUrl' => $this->getToolUrl(),
            'publicKeySet' => $this->getPublicKeySet(),
            'loginUrl' => $this->getLoginUrl(),
            'redirectionUrl' => $this->getRedirectionUrl(),
            'deepLinkUrl' => $this->getDeepLinkUrl(),
            'privateKey' => $this->getPrivateKey(),
            'kid' => $this->getKid()
        ];
    }

    public static function jsonDeserialize($jsonDecoded) {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
