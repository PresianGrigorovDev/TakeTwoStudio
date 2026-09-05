<?php

$groups = [
    'FaqResource.php' => 'Услуги',
    'InquiryResource.php' => 'Запитвания',
    'NavigationItemResource.php' => 'Настройки',
    'OrderResource.php' => 'Запитвания',
    'PageContentResource.php' => 'Съдържание',
    'PartnerResource.php' => 'За Нас',
    'PortfolioCategoryResource.php' => 'Портфолио',
    'PortfolioItemResource.php' => 'Портфолио',
    'ReasonToChooseResource.php' => 'За Нас',
    'ServiceExtraResource.php' => 'Услуги',
    'ServicePackageResource.php' => 'Услуги',
    'ServiceResource.php' => 'Услуги',
    'SiteSettingResource.php' => 'Настройки',
    'SocialMediaResource.php' => 'Настройки',
    'TeamMemberResource.php' => 'За Нас',
    'TestimonialResource.php' => 'За Нас',
];

$dir = 'app/Filament/Resources/';

foreach ($groups as $file => $group) {
    if (file_exists($dir . $file)) {
        $content = file_get_contents($dir . $file);
        if (strpos($content, '$navigationGroup') === false) {
            $replacement = "    protected static ?string \$model = ";
            $insertion = "    protected static ?string \$navigationGroup = '$group';\n\n    protected static ?string \$model = ";
            
            $content = str_replace($replacement, $insertion, $content);
            file_put_contents($dir . $file, $content);
            echo "Updated $file with group $group\n";
        } else {
            echo "Group already exists in $file\n";
        }
    }
}
