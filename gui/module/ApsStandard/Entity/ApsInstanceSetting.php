<?php

namespace iMSCP\ApsStandard\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class ApsInstance
 * @package iMSCP\ApsStandard\Entity
 * @ORM\Table(
 *  name="aps_instance_setting",
 *  indexes={@ORM\Index(columns={"instance_id"})},
 *  options={"collate"="utf8_unicode_ci", "charset"="utf8", "engine"="InnoDB"}
 * )
 * @ORM\Entity
 * @JMS\AccessType("public_method")
 * @Assert\Callback("validateSettingValue")
 */
class ApsInstanceSetting
{
	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned":true})
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 * @JMS\ReadOnly()
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
	 * @JMS\Type("string")
	 */
	private $group;

	/**
	 * @var string
	 * @JMS\Type("string")
	 */
	private $label;

	/**
	 * @var string
	 * @JMS\Type("string")
	 */
	private $description;

	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(name="value", type="text", length=65535, nullable=false)
	 * @JMS\Type("string")
	 */
	private $value;

	/**
	 * @var array
	 * @ORM\Column(name="metadata", type="json_array", nullable=false)
	 * @JMS\Type("array")
	 */
	private $metadata;

	/**
	 * Get instance setting identifier
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
	 * Get setting group
	 *
	 * @return string
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * Set setting group
	 *
	 * @param string $group Setting group
	 * @return ApsInstanceSetting
	 */
	public function setGroup($group)
	{
		$this->group = $group;
		return $this;
	}

	/**
	 * Get setting label
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * Set setting label
	 *
	 * @param string $label Setting label
	 * @return ApsInstanceSetting
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * Get setting description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set setting description
	 *
	 * @param string $description Setting description
	 * @return ApsInstanceSetting
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * Get instance setting name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set instance setting name
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
	 * Get instance setting value
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Set instance setting value
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
	 * Get instance setting metadata
	 *
	 * @return array
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}

	/**
	 * Set instance setting metadata
	 *
	 * @param array $metadata
	 * @return $this
	 */
	public function setMetadata(array $metadata)
	{
		$this->metadata = $metadata;
		return $this;
	}

	/**
	 * Validate instance setting value
	 *
	 * @throws \Exception
	 * @param ExecutionContextInterface $context
	 * @return void
	 */
	public function validateSettingValue(ExecutionContextInterface $context)
	{
		$metadata = $this->getMetadata();

		if (empty($metadata)) {
			throw new \Exception('Could not validate setting without metadata');
		}

		$value = $this->getValue();
		$errorMessages = [];

		switch ($metadata['type']) {
			case 'boolean':
				if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === NULL) {
					$errorMessages[] = tr("Invalid '%s' setting. Boolean expected.", $metadata['label']);
				}
				break;
			case 'string':
			case 'password':
				if (isset($metadata['minlength']) && strlen($value) < intval($metadata['minlength'])) {
					$errorMessages[] = tr(
						"Invalid '%s' setting length. Min. length: %s", $metadata['label'], $metadata['minlength']
					);
				}

				if (
					isset($metadata['maxlength']) && $metadata['maxlength'] !== '' &&
					strlen($value) > intval($metadata['maxlength'])
				) {
					$errorMessages[] = tr(
						"Invalid '%s' setting length. Max. length: %s", $metadata['label'], $metadata['maxlength']
					);
				}

				if (isset($metadata['regexp']) && !preg_match('/' . $metadata['regexp'] . '/', $value)) {
					$errorMessages[] = tr("Invalid '%s' setting syntax.", $metadata['label']);
				}

				break;
			case 'integer':
				if (filter_var($value, FILTER_VALIDATE_INT) === false) {
					$errorMessages[] = tr("Invalid '%s' setting. Integer expected.", $metadata['label']);
				}
				break;
			case 'float':
				if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
					$errorMessages[] = tr("Invalid '%s' setting. Float expected.", $metadata['label']);
				}
				break;
			case 'date':
				// TODO
				break;
			case 'time':
				// TODO
				break;
			case 'domain-name':
				if (!isValidDomainName($value)) {
					$errorMessages[] = tr("Invalid '%s' setting. Domain name expected.", $metadata['label']);
				}

				if (!\iMSCP_Validate::getInstance()->hostname($value, ['allow' => \Zend_Validate_Hostname::ALLOW_DNS])) {
					$errorMessages[] = tr("Invalid '%s' setting. Domain name expected.", $metadata['label']);
				}
				break;
			case 'host-name':
				if (
				!\iMSCP_Validate::getInstance()->hostname($value, [
					'allow' => \Zend_Validate_Hostname::ALLOW_DNS | \Zend_Validate_Hostname::ALLOW_IP
				])
				) {
					$errorMessages[] = tr("Invalid '%s' setting. Hostname or IP address expected.", $metadata['label']);
				}
				break;
			case 'email':
				if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
					$errorMessages[] = tr("Invalid '%s' setting. Email address expected.", $metadata['label']);
				}
				break;
			case 'enum':
				if (in_array($value, $metadata['choices'])) {
					$errorMessages[] = tr("Unexpected value for the '%s' setting.", $metadata['label']);
				}
		}

		if (count($errorMessages) > 0) {
			foreach ($errorMessages as $error) {
				$context->buildViolation($error)->addViolation();
			}
		}
	}
}
