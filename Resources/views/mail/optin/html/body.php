<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 * @var string $url
 */
?>
<tr class="rowBodyContent">
    <td>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr class="rowBodyContent-section rowBodyContent-section--header">
                <td>
                    <h1 class="Title--1">
                        <?php _e('Vérification de votre inscription à la lettre d\'informations', 'theme'); ?>
                    </h1>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--body">
                <td>
                    <p>
                        <?php printf(__(
                            'Félicitations, votre inscription sur le site %s a bien été enregistrée !', 'theme'
                        ), get_bloginfo('name')); ?>
                    </p>
                    <p>
                        <?php _e('Cliquez sur le lien suivant pour valider votre inscription', 'theme'); ?> :
                    </p>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--footer">
                <td>
                    <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0"
                           style="margin: auto;">
                        <tr>
                            <td class="ButtonTd--1">
                                <?php echo partial('tag', [
                                    'attrs'   => [
                                        'class'         => 'Button--1',
                                        'clicktracking' => 'off',
                                        'href'          => $this->get('verify_optin_url'),
                                        'target'        => '_blank',
                                        'title'         => __('Validation de votre inscription', 'theme'),
                                    ],
                                    'content' => __('Valider mon inscription', 'theme'),
                                    'tag'     => 'a',
                                ]); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>