<!-- Modal -->
<div class="modal fade" id="creneauMailInput" tabindex="-1" aria-labelledby="modalInputLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <p>Envoi invitation aux participants</p>
            </div>
            <div class="row mt-2">
                <div class="col-6 ms-3" id="creneauBoxDesc">
                    <p>Créneau</p>
                    <span id="creneauInfo"></span>
                    <hr>
                    <p>Participants</p>
                    <ul id="creneauMailParticipant_ul"></ul>
                </div>
                <div class="col-5 border-start" id="creneauBoxInput">
                    <div class="row">
                        <label for="titrecreneau" class="form-label">Titre de l'évenement * </label>
                        <div id="titrecreneau" class="input-group mb-3">
                            <input class="form-control" type="text" disabled placeholder="Titre de l'évenement"
                                name="titrecreneau" value="<?= $fbParams->titleEvent; ?>"
                                oninvalid="this.setCustomValidity('Veuillez renseigner un titre')"
                                onchange="if(this.value.length>0) this.setCustomValidity('')" />
                        </div>
                    </div>
                    <div class="row">
                        <label for="summarycreneau" class="form-label">Description * </label>
                        <div id="summarycreneau" class="input-group mb-3">
                            <textarea class="form-control" type="textarea" disabled placeholder="Description"
                                name="summarycreneau" <?php echo (isset($fbParams->descriptionEvent)) ? 'value="'.$fbParams->descriptionEvent . '"':''; ?>
                                oninvalid="this.setCustomValidity('Veuillez renseigner une description')"
                                onchange="if(this.value.length>0) this.setCustomValidity('')"><?= $fbParams->descriptionEvent; ?></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col" id='colLieu'>
                            <label class='form-label' for="lieucreneau">Lieu * </label>
                            <div id="lieucreneau" class="input-group">
                                <input class='form-control' type="text" disabled placeholder="Lieu"
                                    name="lieucreneau" value="<?php if (!is_null($fbParams->lieuEvent) && !str_contains($fbParams->lieuEvent, 'https://pantheon')) echo $fbParams->lieuEvent; ?>"
                                    oninvalid="this.setCustomValidity('Veuillez renseigner un lieu')"
                                    onchange="if(this.value.length>0) this.setCustomValidity('')" />
                            </div>
                        </div>
                        <div id="zoom" class="col d-flex align-items-end">
                            <button name="zoom" type="button" class="btn btn-secondary btn-success" data-mdb-ripple-init>Créer un Zoom</button>
                        </div>
                    </div>
                </div>
                <input type="datetime-local" disabled hidden="hidden" name="modalCreneauStart" />
                <input type="datetime-local" disabled hidden="hidden" name="modalCreneauEnd" />
            </div>
            <div class="modal-footer border-0 me-3 pe-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <input type="submit" class="btn btn-primary" name="submitModal" value="Envoyer" formnovalidate />
            </div>
        </div>
    </div>
</div>
