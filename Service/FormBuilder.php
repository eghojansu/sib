<?php

namespace Eghojansu\Bundle\SetupBundle\Service;

use RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class FormBuilder
{
	/** @var Symfony\Component\Form\FormFactoryInterface */
	private $formFactory;

    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    /** @var Symfony\Component\Translation\TranslatorInterface */
    private $translator;


	public function __construct(
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        Setup $setup
    ) {
		$this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->setup = $setup;
	}

    /**
     * Build login form
     *
     * @return Symfony\Component\Form\FormInterface
     */
    public function createLoginForm()
    {
        return $this->formFactory
            ->createBuilder(FormType::class)
            ->add('passphrase', PasswordType::class, [
                'label' => 'Passphrase',
                'constraints' => [
                    new NotBlank(),
                    new IdenticalTo([
                        'value' => $this->setup->getPassphrase(),
                        'message' => 'Wrong passphrase',
                    ]),
                ],
            ])
            ->getForm();
    }

    /**
     * Build passphrase form
     *
     * @return Symfony\Component\Form\FormInterface
     */
    public function createPassphraseForm()
    {
        return $this->formFactory
            ->createBuilder(FormType::class)
            ->add('old_passphrase', PasswordType::class, [
                'label' => 'Old passphrase',
                'constraints' => [
                    new NotBlank(),
                    new IdenticalTo([
                        'value' => $this->setup->getPassphrase(),
                        'message' => 'Wrong passphrase',
                    ]),
                ],
            ])
            ->add('new_passphrase', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label'=>'New passphrase'],
                'second_options' => ['label'=>'Repeat new passphrase'],
            ])
            ->getForm();
    }

    /**
     * Build maintenance form
     *
     * @return Symfony\Component\Form\FormInterface
     */
	public function createMaintenanceForm()
	{
		return $this->formFactory
			->createBuilder(FormType::class)
    		->add('maintenance', ChoiceType::class, [
                'expanded' => true,
                'choices' => [
                    'Inactive' => false,
                    'Active' => true,
                ],
    			'label' => 'Maintenance',
                'data' => $this->setup->isMaintenance(),
    		])
    		->getForm();
	}

    /**
     * Build config form
     *
     * @return Symfony\Component\Form\FormInterface
     */
    public function createConfigForm($version)
    {
        $vConfig = $this->setup->getVersion($version);

        $builder = $this->formFactory
            ->createBuilder(FormType::class);
        $groups = [];

        foreach ($vConfig['config'] as $cName => $cVal) {
            $value = $this->setup->getParameter($cName, $cVal['value']);

            if ($cVal['options']) {
                $type = ChoiceType::class;
                $options = [
                    'choices' => array_combine(
                        $cVal['options'],
                        $cVal['options']
                    ),
                    'constraints' => [
                        new Choice([
                            'choices' => $cVal['options'],
                            'strict' => true,
                        ])
                    ],
                    'data' => $value,
                ];
            } else {
                $type = TextType::class;
                $options = [
                    'attr' => ['placeholder' => $cVal['description']],
                    'constraints' => [],
                    'data' => $value,
                    'required' => $cVal['required'],
                ];
                if ($cVal['required']) {
                    $options['constraints'] = new NotBlank();
                }
            }

            if (empty($cVal['group'])) {
                $builder->add($cName, $type, $options);
            } else {
                if (empty($groups[$cVal['group']])) {
                    $groups[$cVal['group']] = [];
                }
                $groups[$cVal['group']][] = [
                    'name'=>$cName,
                    'type'=>$type,
                    'options'=>$options
                ];
            }
        }

        foreach ($groups as $group => $fields) {
            $groupBuilder = $builder->create($group, FormType::class);

            foreach ($fields as $key => $field) {
                $groupBuilder->add(
                    $field['name'],
                    $field['type'],
                    $field['options']
                );
            }

            $builder->add($groupBuilder);
        }

        $this->addParameters($builder, $vConfig['parameters']);

        return $builder->getForm();
    }

    private function addParameters(
        FormBuilderInterface $builder,
        array $parameters
    ) {
        foreach ($parameters['sources'] as $key => $file) {
            $content = $this->setup->getYamlContent($file, $parameters['key']);

            foreach ($content as $parameter => $value) {
                if (!$this->setup->isConfigAllowedInParameters($parameter)) {
                    continue;
                }

                $builder->add($parameter, TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'data' => $this->setup->getParameter($parameter, $value),
                ]);
            }
        }
    }
}
