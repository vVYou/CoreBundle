<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Entity;

use \RuntimeException;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Claroline\CoreBundle\Library\Security\PlatformRoles;
use Claroline\CoreBundle\Entity\Resource\ResourceRights;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;

/**
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\RoleRepository")
 * @ORM\Table(name="claro_role")
 * @ORM\HasLifecycleCallbacks
 * @DoctrineAssert\UniqueEntity("name")
 */
class Role implements RoleInterface
{
    const PLATFORM_ROLE = 1;
    const WS_ROLE = 2;
    const CUSTOM_ROLE = 3;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(unique=true)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @ORM\Column(name="translation_key")
     */
    protected $translationKey;

    /**
     * @ORM\Column(name="is_read_only", type="boolean")
     */
    protected $isReadOnly = false;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     mappedBy="roles"
     * )
     */
    protected $users;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Tool\OrderedTool",
     *     mappedBy="roles"
     * )
     */
    protected $orderedTools;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Group",
     *     mappedBy="roles"
     * )
     */
    protected $groups;

    /**
     * @ORM\Column(type="integer")
     */
    protected $type = self::PLATFORM_ROLE;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Resource\ResourceRights",
     *     mappedBy="role"
     * )
     */
    protected $resourceRights;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace",
     *     inversedBy="roles"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $workspace;

    public function __construct()
    {
        $this->users           = new ArrayCollection();
        $this->resourceContext = new ArrayCollection();
        $this->groups          = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the role name. The name must be prefixed by 'ROLE_'. Note that
     * platform-wide roles (as listed in Claroline/CoreBundle/Security/PlatformRoles)
     * cannot be modified by this setter.
     *
     * @param string $name
     * @throws \RuntimeException if the name isn't prefixed by 'ROLE_' or if the role is platform-wide
     */
    public function setName($name)
    {
        if (0 !== strpos($name, 'ROLE_')) {
            throw new RuntimeException('Role names must start with "ROLE_"');
        }

        if (PlatformRoles::contains($this->name)) {
            throw new RuntimeException('Platform roles cannot be modified');
        }

        if (PlatformRoles::contains($name)) {
            $this->isReadOnly = true;
        }

        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTranslationKey($key)
    {
        $this->translationKey = $key;
    }

    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    public function isReadOnly()
    {
        return $this->isReadOnly;
    }

    /**
     * Alias of getName().
     *
     * @return string The role name.
     */
    public function getRole()
    {
        return $this->getName();
    }

    /**
     * @ORM\PreRemove
     */
    public function preRemove()
    {
        if (PlatformRoles::contains($this->name)) {
            throw new RuntimeException('Platform roles cannot be deleted');
        }
    }

    public function setReadOnly($value)
    {
        $this->isReadOnly = $value;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function addUser($user)
    {
        $this->users->add($user);

        if ($user->hasRole($this)) {
            $user->addRole($this);
        }
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @todo check the type is a Role class constant
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function addResourceRights(ResourceRights $rc)
    {
        $this->resourceRights->add($rc);
    }

    public function getResourceRights()
    {
        return $this->resourceRights;
    }

    public function setWorkspace(AbstractWorkspace $ws = null)
    {
        $this->workspace = $ws;
    }

    public function getWorkspace()
    {
        return $this->workspace;
    }
}
