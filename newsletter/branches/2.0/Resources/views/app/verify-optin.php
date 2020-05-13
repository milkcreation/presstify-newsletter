<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php $this->layout('newsletter::app/layout'); ?>
<br>
<br>
<br>
<h1><?php _e('Vérification de votre inscription', 'theme'); ?></h1>
<?php echo $this->get('notice'); ?>