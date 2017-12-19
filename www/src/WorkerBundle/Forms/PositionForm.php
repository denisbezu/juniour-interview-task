<?php
/**
 * Created by PhpStorm.
 * User: denys
 * Date: 19.12.17
 * Time: 13:09
 */

namespace WorkerBundle\Forms;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WorkerBundle\Forms\Models\PositionModel;

class PositionForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('positionname', TextType::class, array(
            'label' => 'Должность',
        ));

        $builder->add('submit', SubmitType::class, [
            'label' => 'Добавить',
            'attr' => array('class' => 'btn btn-success')
        ]);
    }

    // ассоциируем модель
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PositionModel::class
        ]);
    }
}