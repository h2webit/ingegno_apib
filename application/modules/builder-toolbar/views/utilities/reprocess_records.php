<?php
// Assicurati che questa query sia eseguita nel posto giusto nel tuo codice,
// possibilmente in un controller o in un modello
$entities = $this->db->query("SELECT * FROM entity WHERE entity_type = 1")->result_array();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form di Riprocessamento</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo base_url(); ?>builder-toolbar/utilities/reprocess_records" method="post" target="_blank">
            <?php add_csrf(); ?>
            <div class="form-group">
                <label for="entita">Entità da riprocessare:</label>
                <select name="entita" id="entita" class="form-control select2_standard" required>
                    <?php foreach ($entities as $entity): ?>
                        <option value="<?php echo htmlspecialchars($entity['entity_name']); ?>">
                            <?php echo htmlspecialchars($entity['entity_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="azione">Azione da triggerare:</label>
                <select class="form-control" id="azione" name="azione" required>
                    <option value="save" disabled>Save</option>
                    <option value="edit">Edit</option>
                    <option value="insert" disabled>Insert</option>
                    <option value="delete" disabled>Delete</option>
                </select>
            </div>

            <div class="form-group">
                <label for="data_inizio">Data inizio:</label>
                <input type="date" class="form-control" id="data_inizio" name="data_inizio" >
            </div>

            <div class="form-group">
                <label for="data_fine">Data fine:</label>
                <input type="date" class="form-control" id="data_fine" name="data_fine" >
            </div>

            <button type="submit" class="btn btn-primary">Invia</button>
        </form>
    </div>
</div>