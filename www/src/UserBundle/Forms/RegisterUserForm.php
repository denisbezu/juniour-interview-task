<?php
/**
 * Created by PhpStorm.
 * User: Denys
 * Date: 07.12.2017
 * Time: 21:22
 */

namespace UserBundle\Forms;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Forms\Models\RegisterUserModel;

class RegisterUserForm extends AbstractType
{
    /**
     *задаем параметрі формы
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'Имя',
            'attr' => array('class' => 'form-control'),
        ]);
        $builder->add('email', RepeatedType::class, array(
            'type' => EmailType::class,
            'invalid_message' => 'Неверный email адрес',
            'options' => array('attr' => array('class' => 'form-control')),
            'required' => true,
            'first_options'  => array('label' => 'Email'),
            'second_options' => array('label' => 'Повторите email'),
        ));

        $builder->add('password', RepeatedType::class, array(
            'type' => PasswordType::class,
            'invalid_message' => 'Пароли должны совпадать',
            'options' => array('attr' => array('class' => 'form-control')),
            'required' => true,
            'first_options'  => array('label' => 'Пароль'),
            'second_options' => array('label' => 'Повторите пароль'),
        ));

        $builder->add('birthday', BirthdayType::class, array(
            //'attr' => array('class' => 'form-control', 'id' => 'datetimepicker'),
            'label' => 'Дата рождения'
        ));

        $builder->add('region', CountryType::class,
            [
                'label' => 'Страна',
                'attr' => array('class' => 'form-control')
            ]);
        $builder->add('submit', SubmitType::class,[
            'label' => 'Зарегистрироваться',
            'attr' => array('class' => 'btn btn-success')
        ]);
    }

    /**
     * задаем класс данных
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegisterUserModel::class
        ]);
    }

}