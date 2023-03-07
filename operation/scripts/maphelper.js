function expandImage(img) {
    var modal = document.getElementById("image-modal");
    var modalImg = document.getElementById("modal-image");
    modal.style.display = "block";
    modalImg.src = img.src;
  
    document.addEventListener("keydown", closeModalOnKeyDown);
  }
  
  function closeModal() {
    var modal = document.getElementById("image-modal");
    modal.style.display = "none";
    document.removeEventListener("keydown", closeModalOnKeyDown);
  }
  
  function closeModalOnKeyDown(event) {
    closeModal();
  }
  