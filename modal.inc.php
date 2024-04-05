<!-- Modal -->
<div class="modal fade" id="creneauMailInput" tabindex="-1" aria-labelledby="modalInputLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <p>Envoi invitation aux participants</p>
            </div>
            <div class="row mt-2">
                <div class="col-6 ms-3" id="creneauBoxDesc">
                    <p>Créneau</p>
                    <span id="creneauInfo" class="text-nowrap"></span>
                    <hr>
                    <p>Participants</p>
                    <ul id="creneauMailParticipant_ul" />
                </div>
                <div class="col-5 border-start" id="creneauBoxInput">
                    <div class="row">
                        <label for="titrecreneau" class="form-label">Titre de l'évenement</label>
                        <div class="input-group mb-3">
                            <input id="titrecreneau" class="form-control" type="text" disabled placeholder="Titre de l'évenement"
                                name="titrecreneau" value="<?= $fbParams->titleEvent; ?>"
                                oninvalid="this.setCustomValidity('Veuillez renseigner un titre')"
                                onchange="if(this.value.length>0) this.setCustomValidity('')" />
                        </div>
                    </div>
                    <div class="row">
                        <label for="summarycreneau" class="form-label">Description</label>
                        <div class="input-group mb-3">
                            <input id="summarycreneau" class="form-control" type="text" disabled placeholder="Description"
                                name="summarycreneau" value="<?= $fbParams->descriptionEvent; ?>"
                                oninvalid="this.setCustomValidity('Veuillez renseigner une description')"
                                onchange="if(this.value.length>0) this.setCustomValidity('')" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <label class='form-label' for="lieucreneau">Lieu :</label>
                            <div class="input-group">
                                <input id="lieucreneau" class='form-control' type="text" disabled placeholder="Lieu"
                                    name="lieucreneau" value="<?= $fbParams->lieuEvent; ?>"
                                    oninvalid="this.setCustomValidity('Veuillez renseigner un lieu')"
                                    onchange="if(this.value.length>0) this.setCustomValidity('')" />
                            </div>
                        </div>
                        <div class="col d-flex align-items-end">
                            <button type="button" class="btn btn-secondary" data-mdb-ripple-init disabled>Zoom créé</button>
                        </div>
                    </div>
                </div>
                <input type="datetime-local" disabled hidden="hidden" name="modalCreneauStart" />
                <input type="datetime-local" disabled hidden="hidden" name="modalCreneauEnd" />
            </div>
            <div class="modal-footer border-0 me-3 pe-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <input type="submit" class="btn btn-primary" name="submitModal" value="Envoyer" />
            </div>
        </div>
    </div>
</div>
