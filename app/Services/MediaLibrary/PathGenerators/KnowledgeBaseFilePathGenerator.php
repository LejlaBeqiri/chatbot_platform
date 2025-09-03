<?php

namespace App\Services\MediaLibrary\PathGenerators;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class KnowledgeBaseFilePathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $knowledgeBase = $media->model;

        return 'knowledge_bases/'.$knowledgeBase->tenant_id.'/'.$knowledgeBase->id.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }
}
