<script>const eventoWsUrl="<?= $stdEnv->eventoWsUrl ?>";</script>
<script>
    // méthode copie url evento index
    function copyClipboard(event) {
        let url = $("#evento + span[type='button'] i").attr('data-creneau-url');

        if ($(event.target).hasClass('bi-clipboard')) {
            $(event.target).removeClass('bi-clipboard');
            $(event.target).addClass('bi-check2');
        }

        navigator.clipboard.writeText(url);
    }
</script>
<!-- Modal -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-labelledby="modalInputLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div id="eventoModalHeader" class="d-inline-flex">
                    <p><?= $isEventoSession ? "Mise à jour":"Creation" ?> de l'evento</p>
                    <div class="fs-2 ps-5 fw-bold">
                        <a href="<?= ($isEventoSession && $fbEventoSession->event['path']) ? $fbEventoSession->event['path'] : '#'  ?>" target='_blank'><?= ($isEventoSession && $fbEventoSession->titreEvento) ? $fbEventoSession->titreEvento: ''  ?></a>
                        <span  type="button" class="btn-clipboard <?= $isEventoSession ? '' : 'd-none' ?>" title="Copier le lien" onclick="copyClipboard(event)"><i class="bi bi-clipboard h4 d-inline-flex"></i></span>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-6 ms-3">
                    <p id="modalEventoP">Créneau<?php if (isset($listDate) && $listDate > 1): ?>x<?php endif ?></p>
                    <span class="text-nowrap"></span>
                    <?php
                        $formatter_day = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd MMMM yyyy");
                        $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "dd/MM/yyyy HH'h'mm");
                        $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm");
                    ?>
                    <ul id='modalEventoCreneaux' class="list-group">
                    <?php for ($idx = 0; $idx < count($listDate) && $date = $listDate[$idx]; $idx++): ?>
                        <li class="list-group-item p-0">
                            <input type="checkbox" name="idxCreneauxChecked[]" value="<?= $idx ?>" class="col-1 mb-2" <?= $fbParams->idxCreneauxChecked == null ? 'checked' : (in_array($idx, $fbParams->idxCreneauxChecked) ? 'checked' : '') ?> />
                            <?= $formatter_day->format($date ->startDate->getTimestamp()) . ' de ' . str_replace(':', 'h', $date->startDate->format('H:i')) . ' à ' . str_replace(':','h', $date->endDate->format('H:i'));?>
                        </li>
                    <?php endfor ?>
                    </ul>
                    <hr>
                    <p>Participants</p>
                    <ul id='modalEventoParticipants'>
                    <?php if (isset($fbForm)): ?>
                    <?php foreach ($fbForm->getFbUsers() as $fbUser): ?>
                        <li><?= $fbUser->getDisplayName() ?></li>
                    <?php endforeach ?>
                    <?php endif ?>
                    </ul>
                </div>
                <div class="col-5 border-start">
                    <div class="row">
                        <label for="titrevento" class="form-label">Titre de l'évenement * </label>
                        <div class="input-group mb-3">
                            <input class="form-control" type="text" placeholder="Titre de l'évenement" name="titrevento" value="<?= $fbParams->titreEvento ? $fbParams->titreEvento : ($fbEventoSession->donneeExistante ? $fbEventoSession->titreEvento : ($fbParams->titleEvent ? $fbParams->titleEvent : '')); ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un titre')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                        </div>
                    </div>
                    <div class="row">
                        <label for="summaryevento" class="form-label">Description * </label>
                        <div class="input-group mb-3">
                            <textarea class="form-control" type="textarea" placeholder="Description" name="summaryevento" oninvalid="this.setCustomValidity('Veuillez renseigner une description')" onchange="if(this.value.length>0) this.setCustomValidity('')"><?= $fbParams->summaryevento ? $fbParams->summaryevento : ($fbEventoSession->donneeExistante ? $fbEventoSession->summaryevento : ($fbParams->descriptionEvent ? $fbParams->descriptionEvent : '')); ?></textarea>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="AuthEvento" <?= ($isEventoSession && $fbEventoSession->event['settings']['reply_access'] == "opened_to_everyone") ?: "checked" ?>>
                        <label class="form-check-label" for="AuthEvento">Participants authentifiés</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="NotifEvento" <?= ($isEventoSession && $fbEventoSession->event['notify_new_guests'] == 'false') ?: "checked" ?>>
                        <label class="form-check-label" for="NotifEvento">Inviter les participants</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 me-3 pe-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" id="eventoSubmit" class="btn btn-primary" name="submitEvento" formnovalidate><?= $isEventoSession ? "Mettre à jour" : "Créer" ?> Evento</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="spinnerEvento" aria-hidden="true" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
      <div class="modal-body d-flex justify-content-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
      </div>
  </div>
</div>
