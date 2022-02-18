<?php


namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\LtiToolRepository")
 * @ORM\Table(name="lti_tools")
 */
class LtiTool{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Classroom\Entity\Applications")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", onDelete="CASCADE",nullable=false)
     */
    private $application; 

    /**
     * @ORM\Column(name="client_id", type="string",length=255, nullable=false)
     */
    private $clientId = '';

    /**
     * @ORM\Column(name="deployment_id", type="string", length=255, nullable=false)
     */
    private $deploymentId = ''; 

    /**
     * @ORM\Column(name="tool_url", type="string", length=255, nullable=false)
     */
    private $toolUrl = ''; 

    /**
     * @ORM\Column(name="public_key_set", type="string", length=255, nullable=false)
     */
    private $publicKeySet; 

    /**
     * @ORM\Column(name="login_url", type="string", length=255, nullable=false)
     */
    private $loginUrl; 

    /**
     * @ORM\Column(name="redirection_url", type="string", length=255, nullable=false)
     */
    private $redirectionUrl; 

    /**
     * @ORM\Column(name="deeplink_url", type="string", length=255, nullable=false)
     */
    private $deepLinkUrl; 

    /**
     * @ORM\Column(name="private_key", type="string", length=10000, nullable=false)
     */
    private $privateKey; 

    /**
     * @ORM\Column(name="kid", type="string", length=255, nullable=false)
     */
    private $kid; 



    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of applicationId
     */ 
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Set the value of applicationId
     *
     * @return  self
     */ 
    public function setApplication($application)
    {
        if(!($application instanceof Applications)){
            throw new EntityDataIntegrityException("The application has to be an instance of Applications class");
        }
        $this->application = $application;

        return $this;
    }

    /**
     * Get the value of clientId
     */ 
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set the value of clientId
     *
     * @return  self
     */ 
    public function setClientId($clientId)
    {
        if(!is_string($clientId)){
            throw new EntityDataIntegrityException("The client id has to be a string value");
        }
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get the value of deploymentId
     */ 
    public function getDeploymentId()
    {
        return $this->deploymentId;
    }

    /**
     * Set the value of deploymentId
     *
     * @return  self
     */ 
    public function setDeploymentId($deploymentId)
    {
        if(!is_string($deploymentId)){
            throw new EntityDataIntegrityException("The deployment id has to be a string");
        }
        $this->deploymentId = $deploymentId;

        return $this;
    }

    /**
     * Get the value of toolUrl
     */ 
    public function getToolUrl()
    {
        return $this->toolUrl;
    }

    /**
     * Set the value of toolUrl
     *
     * @return  self
     */ 
    public function setToolUrl($toolUrl)
    {
        if(!is_string($toolUrl)){
            throw new EntityDataIntegrityException("The tool url has to be a string value");
        }
        $this->toolUrl = $toolUrl;

        return $this;
    }

    /**
     * Get the value of publicKeySet
     */ 
    public function getPublicKeySet()
    {
        return $this->publicKeySet;
    }

    /**
     * Set the value of publicKeySet
     *
     * @return  self
     */ 
    public function setPublicKeySet($publicKeySet)
    {
        $this->publicKeySet = $publicKeySet;

        return $this;
    }

    /**
     * Get the value of loginUrl
     */ 
    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * Set the value of loginUrl
     *
     * @return  self
     */ 
    public function setLoginUrl($loginUrl)
    {
        $this->loginUrl = $loginUrl;

        return $this;
    }

    /**
     * Get the value of redirectionUrl
     */ 
    public function getRedirectionUrl()
    {
        return $this->redirectionUrl;
    }

    /**
     * Set the value of redirectionUrl
     *
     * @return  self
     */ 
    public function setRedirectionUrl($redirectionUrl)
    {
        $this->redirectionUrl = $redirectionUrl;

        return $this;
    }

    /**
     * Get the value of deepLinkUrl
     */ 
    public function getDeepLinkUrl()
    {
        return $this->deepLinkUrl;
    }

    /**
     * Set the value of deepLinkUrl
     *
     * @return  self
     */ 
    public function setDeepLinkUrl($deepLinkUrl)
    {
        $this->deepLinkUrl = $deepLinkUrl;

        return $this;
    }

    /**
     * Get the value of privateKey
     */ 
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set the value of privateKey
     *
     * @return  self
     */ 
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get the value of kid
     */ 
    public function getKid()
    {
        return $this->kid;
    }

    /**
     * Set the value of kid
     *
     * @return  self
     */ 
    public function setKid($kid)
    {
        $this->kid = $kid;

        return $this;
    }
}