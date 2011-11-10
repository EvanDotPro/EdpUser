<?php

namespace EdpUser\Mapper;

use Doctrine\ORM\EntityManager,
    EdpUser\Module,
    EdpUser\ModelBase\UserBase,
    EdpCommon\EventManager\EventProvider,
    SpiffyDoctrine\Authentication\Adapter\DoctrineEntity as DoctrineAuthAdapter,
    SpiffyDoctrine\Validator\NoEntityExists;

class UserDoctrine extends EventProvider implements UserInterface
{
    protected $authAdapter;
    protected $em;
    protected $emailValidator;

    public function persist(UserBase $user)
    {
        $em = $this->getEntityManager();
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('user' => $user, 'em' => $em));
        $em->persist($user);
        $this->events()->trigger(__FUNCTION__ . '.post', $this, array('user' => $user, 'em' => $em));
        $em->flush();
    }

    public function findByEmail($email)
    {
        $user = $this->getUserRepository()->findOneBy(array('email' => $email));
        $this->events()->trigger(__FUNCTION__, $this, array('user' => $user));
        return $user;
    }

    public function findByUsername($username)
    {
        $user = $this->getUserRepository()->findOneBy(array('username' => $username));
        $this->events()->trigger(__FUNCTION__, $this, array('user' => $user));
        return $user;
    }

    public function getAuthAdapter($identity, $credential, $identityColumn)
    {
    	$class = Module::getOption('user_model_class'); // doesn't work if they change it in Application
    	$class = 'Application\Entity\User';
        if (null === $this->authAdapter) {
            $authAdapter = new DoctrineAuthAdapter(
                $this->getEntityManager(),
                $class
            );
            $this->authAdapter = $authAdapter;
        }
        $this->authAdapter->setIdentity($identity)
                          ->setCredential($credential)
                          ->setIdentityColumn($identityColumn);
        return $this->authAdapter;
    }

    public function getEmailValidator()
    {
        if (null === $this->emailValidator) {
            $this->emailValidator = new NoEntityExists(array(
                'em'     => $this->getEntityManager(),
                //'entity' => Module::getOption('user_model_class'), // doesn't work if they change it in Application
                'entity' => 'Application\Entity\User', 
                'field'  => 'email',
            ));
        }
        return $this->emailValidator;
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
        return $this;
    }

    public function getUserRepository()
    {
    	$class = Module::getOption('user_model_class'); // doesn't work if they change it in Application
    	$class = 'Application\Entity\User';
        return $this->getEntityManager()->getRepository($class);
    }

}
