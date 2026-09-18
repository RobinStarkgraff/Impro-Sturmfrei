<?php
/* ------------------------------------------------------------
   The ways to get in touch — one block, and nothing to press.

   It had a page of its own, /kontakt/, and that page has been four shapes:
   five labelled paragraphs, then three whole-card links with a hint line
   each, then two, then this block alone. Now it has none. The address and
   the number are in the Impressum, where the law puts them anyway and
   where "Kontakt" in the header bar leads, and this block closes /buchen/
   — the one page whose readers are about to write to us.

   The cards were the mistake along the way: a card on this site is a thing
   you press — the follow cards, the rows in the list of dates, the ticket
   at the top of /termine/ all are — and dressed as two of those, an
   address and a phone number promised a page behind them that does not
   exist. A mail client opening is not a page, and a dialler on a desktop
   is nothing at all.

   So neither of them is a target any more. What a visitor needs here is
   to READ two facts and keep them: the block holds the address and the
   number first, and under them the line saying what to expect of either.
   The facts open it because they are what somebody came back for — the
   sentence is read once.

   Which means the addresses stand as text. The mailto: and the tel: are
   gone with the cards that carried them — whoever wants to write copies
   the address, the way they would off a poster or a business card.

   The phone only if content/legal.json holds a number. Without one the
   mail address stands alone, which is why the facts are assembled as a
   list first and rendered once.

   All three values are asked for, and none of them has a default. On its
   own page the block needed no heading — the page's own said "Kontakt" —
   and the sentence under the facts could stand in here, because there was
   one caller and it was this file's own page. Standing at the foot of
   somebody else's page it needs both from that page, and a fallback
   nobody passes would only be a second wording to keep up to date:

   $eyebrow, $heading   the heading over the block
   $text                the line under the facts, in the words of the page
                        it stands on — on /buchen/ it says what to put in
                        the mail, which on a page about the ensemble would
                        be an odd thing to ask.
   ------------------------------------------------------------ */

$phone = $legal['impressum']['phone'];

/* "icon" names a file in public/images/icons/, as everywhere else on this
   site. The glyph sits beside the label and says the same thing it does —
   it is decorative, and icon() marks it aria-hidden accordingly. */
$details = [
    [
        'icon' => 'mail',
        'label' => 'E-Mail',
        'value' => $site['email'],
    ],
];

if ($phone) {
    $details[] = [
        'icon' => 'phone',
        'label' => 'Telefon',
        'value' => $phone,
    ];
}
?>
  <!-- ================= CONTACT ================= -->
  <section class="section" id="kontakt">
    <div class="wrap wrap--prose">

      <div class="section-head" data-reveal>
        <p class="eyebrow"><?= esc($eyebrow) ?></p>
        <h2><?= esc($heading) ?></h2>
      </div>

      <div class="contact-panel" data-reveal>

        <dl class="contact-details">
<?php
/* Glyph and label stand on one line and with no space between them: the gap
   between them is the label's own (flex, css/17-contact.css), and an
   indented block would leave a stray text node in the middle of the row —
   plus its indentation in front of the <dt>. */
foreach ($details as $detail): ?>
          <div class="contact-detail">
            <dt class="contact-detail__label"><?= icon($detail['icon'], 'contact-detail__glyph') ?><?= esc($detail['label']) ?></dt>
            <dd class="contact-detail__value"><?= esc($detail['value']) ?></dd>
          </div>
<?php endforeach; ?>
        </dl>

        <p class="contact-panel__text"><?= esc($text) ?></p>

      </div>

    </div>
  </section>
