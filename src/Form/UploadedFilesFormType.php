<?php

namespace App\Form;

use App\Entity\UploadedFiles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UploadedFilesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', FileType::class, [
                'label' => 'Ajouter un fichier',
                'constraints' => [
                    new File([
                        'maxSize' => '2000M',
                        'maxSizeMessage' => 'Le fichier est trop lourd ({{ size }} {{ suffix }}). La taille maximum autorisé est de {{ limit }} {{ suffix }}.',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'video/x-msvideo',
                            'text/csv',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/gif',
                            'image/x-icon',
                            'image/jpeg',
                            'application/json',
                            'application/vnd.oasis.opendocument.presentation',
                            'video/mpeg',
                            'application/vnd.oasis.opendocument.spreadsheet',
                            'application/vnd.oasis.opendocument.text',
                            'image/png',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'application/x-rar-compressed',
                            'image/svg+xml',
                            'application/x-tar',
                            'image/tiff',
                            'image/webp',
                            'application/vnd.ms-excel',
                            'application/xml',
                            'application/zip',
                            'text/plain',
                        ],
                        'mimeTypesMessage' => 'L\'extension du fichier n\'est pas autoriser',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UploadedFiles::class,
        ]);
    }
}
