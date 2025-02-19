<?php

declare(strict_types=1);

/*
 * This file is part of the ConnectHolland CookieConsentBundle package.
 * (c) Connect Holland.
 */

namespace ConnectHolland\CookieConsentBundle\Form;

use ConnectHolland\CookieConsentBundle\Cookie\CookieChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CookieConsentType extends AbstractType
{
    /**
     * @var CookieChecker
     */
    protected $cookieChecker;

    /**
     * @var array
     */
    protected $cookieCategories;

    /**
     * @var bool
     */
    protected $cookieConsentSimplified;

    /**
     * @var bool
     */
    protected $csrfProtection;

    public function __construct(
        CookieChecker $cookieChecker,
        array $cookieCategories,
        bool $cookieConsentSimplified = false,
        bool $csrfProtection = true
    ) {
        $this->cookieChecker           = $cookieChecker;
        $this->cookieCategories        = $cookieCategories;
        $this->cookieConsentSimplified = $cookieConsentSimplified;
        $this->csrfProtection          = $csrfProtection;
    }

    /**
     * Build the cookie consent form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->cookieCategories as $category) {
            $builder->add($category, ChoiceType::class, [
                'expanded' => true,
                'multiple' => false,
                'data'     => $this->cookieChecker->isCategoryAllowedByUser($category) || $category === 'functional' ? 'true' : 'false',
                'choices'  => [
                    ['ch_cookie_consent.yes' => 'true'],
                    ['ch_cookie_consent.no' => 'false'],
                ],
                'attr' => [
                    'data-disabled-false' => $category === 'functional'
                ]
            ]);
        }

        if ($this->cookieConsentSimplified === false) {
            $builder->add('save', SubmitType::class, ['label' => 'ch_cookie_consent.save', 'attr' => ['class' => 'btn ch-cookie-consent__btn ch-cookie-consent__btn--secondary ch-cookie-consent__btn_save']]);
            $builder->add('use_all_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_all_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
        } else {
            $builder->add('use_all_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_all_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn ch-cookie-consent__btn--secondary']]);
            $builder->add('use_only_functional_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_only_functional_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
        }
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (isset($data['use_all_cookies']))
            {
                foreach ($this->cookieCategories as $category) {
                    $data[$category] = 'true';
                }
            }
            elseif(isset($data['functional']))
            {
                $data['functional'] = 'true';
            }

            $event->setData($data);
        });
    }

    /**
     * Default options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'CHCookieConsentBundle',
            'csrf_protection' => $this->csrfProtection,
        ]);
    }
}
