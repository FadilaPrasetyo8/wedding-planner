<?php

namespace App\Enums;

enum TaskDateMode: string
{
    case Manual = 'manual';
    case TemplateRelative = 'template_relative';
}
