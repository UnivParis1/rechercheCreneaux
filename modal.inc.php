<!-- Modal -->
<div class="modal fade" id="creneauMailInput" tabindex="-1" aria-labelledby="modalInputLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <p>Envoi invitation aux participants</p>
            </div>
            <div class="row">
                <div class="col-6" id="creneauBoxDesc">
                    <p>Créneau</p>
                    <span id="creneauInfo" class="text-nowrap text-break"></span>
                    <hr>
                    <p>Participants</p>
                    <ul id="creneauMailParticipant_ul" />
                </div>
                <div class="col-5 align-content-between" id="creneauBoxInput">
                    <label for="titrecreneau">Titre de l'évenement</label>
                    <input id="titrecreneau" type="text" disabled placeholder="Titre de l'évenement"
                        name="titrecreneau" value="<?= $fbParams->titleEvent; ?>"
                        oninvalid="this.setCustomValidity('Veuillez renseigner un titre pour l\'évenement')"
                        onchange="if(this.value.length>0) this.setCustomValidity('')" />
                    <label for="summarycreneau">Description :</label>
                    <textarea id="summarycreneau" disabled placeholder="Description de l'évenement"
                        name="summarycreneau" value="<?= $fbParams->descriptionEvent; ?>"
                        oninvalid="this.setCustomValidity('Veuillez renseigner une description pour l\'évenement')"
                        onchange="if(this.value.length>0) this.setCustomValidity('')"><?= $fbParams->descriptionEvent; ?></textarea>
                    <label for="lieucreneau">Lieu :</label>
                    <input id="lieucreneau" type="text" disabled placeholder="Lieu de l'évenement"
                        name="lieucreneau" value="<?= $fbParams->lieuEvent; ?>"
                        oninvalid="this.setCustomValidity('Veuillez renseigner un lieu pour l\'évenement')"
                        onchange="if(this.value.length>0) this.setCustomValidity('')" />
                </div>
                <input type="datetime-local" disabled hidden="hidden" name="modalCreneauStart" />
                <input type="datetime-local" disabled hidden="hidden" name="modalCreneauEnd" />
            </div>
            <div class="modal-footer" id="creneauBoxFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <input type="submit" class="btn btn-primary" name="submitModal" value="Envoyer" />
                <a id="zoom" href>Creer zoom</a>
            </div>
        </div>
    </div>
</div>
