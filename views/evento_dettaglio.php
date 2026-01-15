<?php
/**
 * Dettaglio singolo evento
 */
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Biglietto.php';

$evento = $_SESSION['evento_corrente'] ?? null;
$intrattenitori = $_SESSION['intrattenitori_evento'] ?? [];
$recensioni = $_SESSION['recensioni_evento'] ?? [];
$mediaVoti = $_SESSION['media_voti'] ?? null;

if (!$evento) {
    echo '<p class="error">Evento non trovato.</p>';
    return;
}

$settori = getSettoriByLocation($pdo, $evento['idLocation']);
$tipi = getAllTipiBiglietto($pdo);
?>

<article class="evento-dettaglio">
    <header>
        <h1><?= e($evento['Nome']) ?></h1>
        <p class="manifestazione">Parte di: <strong><?= e($evento['ManifestazioneName']) ?></strong></p>
    </header>

    <div class="evento-info">
        <div class="info-grid">
            <div class="info-item">
                <strong>Data:</strong>
                <span><?= formatDate($evento['Data']) ?></span>
            </div>
            <div class="info-item">
                <strong>Orario:</strong>
                <span><?= formatTime($evento['OraI']) ?> - <?= formatTime($evento['OraF']) ?></span>
            </div>
            <div class="info-item">
                <strong>Luogo:</strong>
                <span><?= e($evento['LocationName']) ?></span>
            </div>
            <div class="info-item">
                <strong>Prezzo Base:</strong>
                <span><?= formatPrice($evento['PrezzoNoMod']) ?></span>
            </div>
            <?php if ($mediaVoti): ?>
            <div class="info-item">
                <strong>Valutazione:</strong>
                <span><?= $mediaVoti ?>/5 (<?= count($recensioni) ?> recensioni)</span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($evento['Programma']): ?>
        <div class="programma">
            <h3>Programma</h3>
            <p><?= nl2br(e($evento['Programma'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($intrattenitori)): ?>
    <section class="intrattenitori">
        <h2>Intrattenitori</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Mestiere</th>
                    <th>Orario Esibizione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($intrattenitori as $i): ?>
                <tr>
                    <td><?= e($i['Nome']) ?></td>
                    <td><?= e($i['Mestiere']) ?></td>
                    <td><?= formatTime($i['OraI']) ?> - <?= formatTime($i['OraF']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
    <section class="acquista-biglietto">
        <h2>Acquista Biglietto</h2>
        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="acquista">
            <input type="hidden" name="idEvento" value="<?= $evento['id'] ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="cognome">Cognome:</label>
                    <input type="text" id="cognome" name="cognome" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sesso">Sesso:</label>
                    <select id="sesso" name="sesso" required>
                        <option value="M">Maschio</option>
                        <option value="F">Femmina</option>
                        <option value="Altro">Altro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="idClasse">Tipo Biglietto:</label>
                    <select id="idClasse" name="idClasse" required>
                        <?php foreach ($tipi as $tipo): ?>
                        <option value="<?= e($tipo['nome']) ?>">
                            <?= e($tipo['nome']) ?> (+<?= formatPrice($tipo['ModificatorePrezzo']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="idSettore">Settore:</label>
                    <select id="idSettore" name="idSettore" required>
                        <?php foreach ($settori as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            Settore <?= $s['id'] ?> - x<?= $s['MoltiplicatorePrezzo'] ?> (<?= $s['Posti'] ?> posti)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="metodo">Metodo Pagamento:</label>
                    <select id="metodo" name="metodo" required>
                        <option value="Carta">Carta</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Bonifico">Bonifico</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-large">Acquista Biglietto</button>
        </form>
    </section>
    <?php else: ?>
    <div class="login-prompt">
        <p>Per acquistare un biglietto devi <a href="index.php?action=login">effettuare il login</a>.</p>
    </div>
    <?php endif; ?>

    <section class="recensioni">
        <h2>Recensioni</h2>
        <?php if (empty($recensioni)): ?>
            <p class="no-data">Nessuna recensione ancora.</p>
        <?php else: ?>
            <div class="recensioni-list">
                <?php foreach ($recensioni as $r): ?>
                <div class="recensione">
                    <div class="recensione-header">
                        <strong><?= e($r['Nome']) ?> <?= e($r['Cognome']) ?></strong>
                        <span class="voto"><?= str_repeat('&#9733;', $r['Voto']) ?><?= str_repeat('&#9734;', 5 - $r['Voto']) ?></span>
                    </div>
                    <?php if ($r['Messaggio']): ?>
                    <p class="recensione-testo"><?= nl2br(e($r['Messaggio'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isLoggedIn() && !hasRecensito($pdo, $evento['id'], $_SESSION['user_id'])): ?>
        <div class="aggiungi-recensione">
            <h3>Lascia una Recensione</h3>
            <form method="post" action="index.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_recensione">
                <input type="hidden" name="idEvento" value="<?= $evento['id'] ?>">

                <div class="form-group">
                    <label for="voto">Voto:</label>
                    <select id="voto" name="voto" required>
                        <option value="5">5 - Eccellente</option>
                        <option value="4">4 - Ottimo</option>
                        <option value="3">3 - Buono</option>
                        <option value="2">2 - Sufficiente</option>
                        <option value="1">1 - Scarso</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="messaggio">Commento (opzionale):</label>
                    <textarea id="messaggio" name="messaggio" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-secondary">Invia Recensione</button>
            </form>
        </div>
        <?php endif; ?>
    </section>
</article>

<p>
    <a href="index.php?action=list_eventi" class="btn btn-secondary">Torna agli Eventi</a>
</p>
