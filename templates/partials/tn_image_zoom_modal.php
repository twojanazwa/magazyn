<?php // ============================================================ ?>
<?php // --- Modal do powiększania zdjęć (#tnImageZoomModal) --- ?>
<?php // ============================================================ ?>
<div class="modal fade" id="tnImageZoomModal" tabindex="-1" aria-labelledby="tnImageZoomModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header py-2"> <?php // Cieńszy header ?>
				<h6 class="modal-title" id="tnImageZoomModalLabel">Podgląd Zdjęcia Produktu</h6> <?php // Zmniejszono nagłówek ?>
				<button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Zamknij"></button> <?php // Mniejszy przycisk ?>
			</div>
			<div class="modal-body text-center p-2"> <?php // Mniejszy padding ?>
				<img src="" id="tnZoomedImage" class="img-fluid rounded" alt="Powiększone zdjęcie" style="max-height: 85vh; object-fit: contain;"> <?php // Dodano object-fit i rounded ?>
			</div>
	</div></div></div>