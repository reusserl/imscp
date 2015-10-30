<?php

namespace iMSCP\ApsStandard\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class ApsInstance
 *
 * @package iMSCP\ApsStandard\Entity
 * @ORM\Table(
 *  name="aps_instance_setting",
 *  indexes={@ORM\Index(name="instance_id", columns={"instance_id"})}
 * )
 * @ORM\Entity
 * @JMS\AccessType("public_method")
 */
class ApsInstanceSetting
{
	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned":true})
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 * @JMS\Exclude()
	 */
	private $id;

	/**
	 * @var \iMSCP\ApsStandard\Entity\ApsInstance
	 * @ORM\ManyToOne(targetEntity="\iMSCP\ApsStandard\Entity\ApsInstance", inversedBy="settings")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
	 * })
	 * @JMS\Exclude()
	 */
	private $instance;

	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(name="value", type="text", length=65535, nullable=false)
	 * @Assert\Callback("validateSettingValue")
	 */
	private $value;

	/**
	 * @var array
	 * @JMS\ReadOnly()
	 */
	private $metadata = array();

	/**
	 * Get identifier
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get APS instance to which this setting belongs to
	 *
	 * @return ApsInstance
	 */
	public function getInstance()
	{
		return $this->instance;
	}

	/**
	 * Set instance to which this setting belong to
	 *
	 * @param ApsInstance $instance
	 * @return ApsInstanceSetting
	 */
	public function setInstance(ApsInstance $instance)
	{
		$this->instance = $instance;
		return $this;
	}

	/**
	 * Get setting name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set setting name
	 *
	 * @param string $name
	 * @return ApsInstanceSetting
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Get setting value
	 *
	 * @return string
	 */
	public function getValue()
	{
		return (string)$this->value;
	}

	/**
	 * Set setting value
	 *
	 * @param string $value
	 * @return ApsInstanceSetting
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * Get setting metadata
	 *
	 * @return array
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}

	/**
	 * Set setting metadata
	 *
	 * @param array $metadata
	 */
	public function setMetadata($metadata)
	{
		$this->metadata = $metadata;
	}

	/**
	 * Validate setting value
	 *
	 * @throws \Exception
	 * @param ExecutionContextInterface $context
	 * @return void
	 */
	public function validateSettingValue(ExecutionContextInterface $context)
	{
		$metadata = $this->getMetadata();

		if(empty($metadata)) {
			throw new \Exception('Could not validate setting without metadata');
		}

		$value = $this->getValue();
		$errors = array();

		switch ($metadata['aps_type']) {
			case 'string':
				if (isset($metadata['min_length']) && strlen($value) < intval($metadata['min_length'])) {
					$errors[] = tr("Invalid '%s' setting length. Min. length: %s", $metadata['label'], $metadata['min_length']);
				}

				if (isset($metadata['max_length']) && strlen($value) > intval($metadata['max_length'])) {
					$errors[] = tr("Invalid '%s' setting length. Max. length: %s", $metadata['label'], $metadata['max_length']);
				}

				if (isset($metadata['regexp']) && !preg_match('/' . $metadata['regexp'] . '/', $value)) {
					$errors[] = tr("Invalid '%s' setting syntax.", $metadata['label']);
				}

				break;
			case 'integer':
				if (filter_var($value, FILTER_VALIDATE_INT) === false) {
					$errors[] = tr("Invalid '%s' setting. Integer expected.", $metadata['label']);
				}
				break;
			case 'float':
				if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
					$errors[] = tr("Invalid '%s' setting. Float expected.", $metadata['label']);
				}
				break;
			case 'domain-name':
				if (!isValidDomainName($value)) {
					$errors[] = tr("Invalid '%s' setting. Domain name expected.", $metadata['label']);
				}
				break;
			case 'email':
				if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
					$errors[] = tr("Invalid '%s' setting. Email address expected.", $metadata['label']);
				}
				break;
			case 'enum':
				if (!in_array($value, $metadata['choices'])) {
					$errors[] = tr("Unexpected value for the '%s' setting.", $metadata['label']);
				}
		}

		if (count($errors) > 0) {
			foreach ($errors as $error) {
				$context->buildViolation($error)->addViolation();
			}
		}
	}
}
