<section class="content-header">
    <h1>Importer<small><?php e('Import results'); ?></small>
    </h1>
</section>

<section class="content container-fluid">
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Import Finished</h3>
        </div>

        <div class="box-body">
            <?php if ($dati['count'] >= 1) : ?>
                <?php echo $dati['count']; ?> record imported.
            <?php else : ?>
                <?php echo (int) $dati['count']; ?> records imported.
            <?php endif; ?>
            <?php if ($dati['warnings']) : ?>
                <br />
                <br />
                <?php foreach ($dati['warnings'] as $warning) : ?>
                    <span><?php echo $warning; ?></span><br />
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="box-footer clearfix">
            <a href="<?php echo base_url(); ?>" class="btn btn-primary pull-right">Back</a>
        </div>
    </div>
</section>