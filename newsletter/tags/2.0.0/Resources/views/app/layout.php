<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php get_header(); ?>

<div class="NewsletterBody">
    <?php echo $this->section('content'); ?>
</div>

<?php get_footer();