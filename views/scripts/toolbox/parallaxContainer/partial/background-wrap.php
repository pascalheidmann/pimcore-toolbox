<div class="parallax-background" <?= $this->backgroundImageTag ?>>
    <?= $this->partial('toolbox/parallaxContainer/partial/parallax-content.php',
        [
            'behindElements' => $this->behindElements,
            'frontElements'  => $this->frontElements,
            'content'        => $this->content
        ]
    ); ?>
</div>