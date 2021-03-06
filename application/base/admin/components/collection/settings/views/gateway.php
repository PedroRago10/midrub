<section>
    <div class="container-fluid settings">
        <div class="row">
            <div class="col-lg-2 col-lg-offset-1">
                <div class="row">
                    <div class="col-lg-12">
                        <?php md_include_component_file(MIDRUB_BASE_ADMIN_COMPONENTS_SETTINGS . 'views/menu.php'); ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-lg-12 settings-area">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <?php echo $gateway['gateway_icon']; ?>
                                <?php echo $gateway['gateway_name']; ?>
                            </div>
                            <div class="panel-body">
                                <ul class="settings-list-options">
                                    <?php md_get_gateway_fields($gateway['fields']); ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php md_include_component_file(MIDRUB_BASE_ADMIN_COMPONENTS_SETTINGS . 'views/footer.php'); ?>