<!-- Modal -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-labelledby="modalInputLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <p>Creation de l'evento</p>
            </div>
            <div class="row mt-2">
                <div class="col-6 ms-3">
                    <p>Créneau</p>
                    <span class="text-nowrap"></span>
                    <?php 
                        $formatter_day = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd MMMM yyyy");
                        $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "dd/MM/yyyy HH'h'mm");
                        $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm");
                    ?>
                    <ul id='modalEventoCreneaux'>
                    <?php foreach ($listDate as $date): ?>
                    <?php $strD = $formatter_day->format($date->startDate->getTimestamp()) . ' de ' . str_replace(':', 'h', $date->startDate->format('h:i')) . ' à ' . str_replace(':','h', $date->endDate->format('h:i'));?>
                        <li><?= $strD; ?></li>
                    <?php endforeach ?>
                    </ul>
                    <hr>
                    <p>Participants</p>
                    <ul id='modalEventoParticipants'>
                    <?php foreach ($fbForm->getFbUsers() as $fbUser): ?>
                        <li><?= $fbUser->getUidInfos()->displayName ?></li>
                    <?php endforeach ?>
                    </ul>
                </div>
                <div class="col-5 border-start"> 
                    <div class="row">
                        <label for="titrevento" class="form-label">Titre de l'évenement</label>
                        <div class="input-group mb-3">
                            <input class="form-control" type="text" placeholder="Titre de l'évenement" name="titrevento" value="<?= $fbParams->titleEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un titre')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                        </div>
                    </div>
                    <div class="row">
                        <label for="summaryevento" class="form-label">Description</label>
                        <div class="input-group mb-3">
                            <textarea class="form-control" type="textarea" placeholder="Description" name="summaryevento" value="<?= $fbParams->descriptionEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner une description')" onchange="if(this.value.length>0) this.setCustomValidity('')"><?= $fbParams->descriptionEvent; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 me-3 pe-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-primary" name="submit">Créer Evento</button>
            </div>
        </div>
    </div>
</div>
