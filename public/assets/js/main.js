/**
 * WEBSITE: https://themefisher.com
 * TWITTER: https://twitter.com/themefisher
 * FACEBOOK: https://facebook.com/themefisher
 * GITHUB: https://github.com/themefisher/
 */

// import Swiper from '../plugins/swiper/swiper-bundle.js';

(function () {
  "use strict";

  // Preloader js
  // window.addEventListener("load", (e) => {
  //   document.querySelector(".preloader").style.display = "none";
  // });

  //toggle navigation
  const header = document.querySelector(".header");
  const navToggler = document.getElementById("nav-toggle");
  navToggler.addEventListener("change", function () {
    if (this.checked) {
      header.classList.add("nav-open");
    } else {
      header.classList.remove("nav-open");
    }
  });

  //play video
  const video = document.querySelector(".video-iframe");
  if (video) {
    const videoPlayBtn = document.querySelector(".video-play-btn");
    videoPlayBtn.addEventListener("click", () => {
      video.classList.remove("hidden");
    });
  }

  //brands-carousel
  new Swiper(".brands-carousel", {
    slidesPerView: 3,
    spaceBetween: 30,
    loop: true,
    breakpoints: {
      768: {
        slidesPerView: 4,
      },

      1200: {
        slidesPerView: 6,
      },
    },
  });

  //testimonial-carousel
  new Swiper(".testimonials-slider", {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    pagination: {
      el: ".testimonials-pagination",
      type: "bullets",
      clickable: true,
    },
    breakpoints: {
      600: {
        slidesPerView: 2,
      },
      992: {
        slidesPerView: 3,
      },
    },
  });

  //testimonial-carousel
  new Swiper(".jobs-carousel", {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    pagination: {
      el: ".jobs-carousel-pagination",
      type: "bullets",
      clickable: true,
    },
    breakpoints: {
      600: {
        slidesPerView: 2,
      },
      992: {
        slidesPerView: 3,
      },

      1200: {
        spaceBetween: 48,
        slidesPerView: 3,
      },
    },
  });

  //benefits-slider
  new Swiper(".benefits-slider", {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    pagination: {
      el: ".benefits-slider-pagination",
      type: "bullets",
      clickable: true,
    },
    breakpoints: {
      600: {
        slidesPerView: 2,
      },
      992: {
        slidesPerView: 3,
      },

      1200: {
        slidesPerView: 4,
      },
    },
  });

  //jobs-slider
  new Swiper(".jobs-slider", {
    slidesPerView: 1,
    loop: true,
    spaceBetween: 24,
    pagination: {
      el: ".jobs-slider-pagination",
      type: "bullets",
      clickable: true,
    },
  });

  //toggle-price-type
  const priceToggleBtn = document.querySelectorAll(".price-tab-btn");
  if (priceToggleBtn) {
    let prevType = "monthly";
    priceToggleBtn.forEach((btn) => {
      btn.addEventListener("click", function () {
        const type = this.dataset.type;
        if (type === prevType) return;
        [...this.parentElement.children].forEach((ele) => {
          ele.classList.remove("active");
        });
        document.querySelector(".price-cards").classList.toggle("yearly");
        this.classList.add("active");
        prevType = type;
      });
    });
  }

  //show apply form
  const showApplyFormBtn = document.querySelector(".job-apply-btn");
  if (showApplyFormBtn)
    showApplyFormBtn.addEventListener("click", () => {
      document.querySelector(".job-apply-form").classList.toggle("hidden");
    });

  // Accordion component
  const accordion = document.querySelectorAll("[data-accordion]");
  accordion.forEach((header) => {
    header.addEventListener("click", () => {
      const accordionItem = header.parentElement;
      accordionItem.classList.toggle("active");
    });
  });

  // modal components
  const modal = document.getElementById("modal");
  const modalContainer = document.getElementById("modal-container");
  const modalOpenBtn = document.getElementById("modal-open-button");
  const modalCloseBtn = document.getElementById("modal-close-button");

  function intiModal() {
    if (!modal) return;
    // modal open button
    modalOpenBtn.addEventListener("click", () => {
      modal.classList.remove("hidden");
      modalContainer.classList.remove("hidden");
    });

    // modal close button
    modalCloseBtn.addEventListener("click", () => {
      modal.classList.add("hidden");
      modalContainer.classList.add("hidden");
    });

    // Close the modal click on the modal container
    modalContainer.addEventListener("click", () => {
      modal.classList.add("hidden");
      modalContainer.classList.add("hidden");
    });
  }

  intiModal();

  //team shuffle
  const teamElement = document.querySelector(".team-shuffle");
  if (teamElement) {
    const shuffleInstance = new Shuffle(teamElement, {
      itemSelector: ".team-member",
    });
  }

  //counter
  const speed = 200;
  const counter = document.querySelectorAll(".counter-count");
  counter.forEach((counter) => {
    let initialVal = 0;
    const finalVal = +counter.innerText.replace(/\W/gi, "");
    const text = counter.innerText.replace(/\d/gi, "");
    const increment = finalVal / speed;
    let intervalId = setInterval(() => {
      if (initialVal < finalVal) {
        counter.innerText = Math.ceil(initialVal + increment) + text;
        initialVal += increment;
      } else {
        counter.innerText = finalVal + text;
        clearInterval(intervalId);
      }
    }, 5);
  });

  // // for tab component
  // // Get all the tab groups on the page
  // const tabGroups = document.querySelectorAll("[data-tab-group]");
  // // Loop through each tab group
  // tabGroups.forEach((tabGroup) => {
  //   // Get the tabs nav and content for this tab group
  //   const tabsNav = tabGroup.querySelector("[data-tab-nav]");
  //   const tabsNavItem = tabsNav.querySelectorAll("[data-tab]");

  //   // Get the active tab index from local storage, or default to 0 if not set
  //   const activeTabName =
  //     localStorage.getItem(`activeTabName-${tabGroup.dataset.tabGroup}`) ||
  //     tabsNavItem[0].getAttribute("data-tab");

  //   // Set the active tab
  //   setActiveTab(tabGroup, activeTabName);

  //   // Add a click event listener to each tab nav item
  //   tabsNavItem.forEach((tabNavItem) => {
  //     tabNavItem.addEventListener("click", () => {
  //       // Get the index of the clicked tab nav item
  //       const tabName = tabNavItem.dataset.tab;
  //       setActiveTab(tabGroup, tabName);

  //       // Save the active tab index to local storage
  //       localStorage.setItem(
  //         `activeTabName-${tabGroup.dataset.tabGroup}`,
  //         tabName
  //       );
  //     });
  //   });
  // });

  // // Function to set the active tab for a given tab group
  // function setActiveTab(tabGroup, tabName) {
  //   // Get the tabs nav and content for this tab group
  //   const tabsNav = tabGroup.querySelector("[data-tab-nav]");
  //   const tabsContent = tabGroup.querySelector("[data-tab-content]");

  //   // Remove the active class from all tab nav items and content panes
  //   tabsNav.querySelectorAll("[data-tab]").forEach((tabNavItem) => {
  //     tabNavItem.classList.remove("active");
  //   });
  //   tabsContent.querySelectorAll("[data-tab-panel]").forEach((tabPane) => {
  //     tabPane.classList.remove("active");
  //   });

  //   // Add the active class to the selected tab nav item and content pane
  //   const selectedTabNavItem = tabsNav.querySelector(`[data-tab="${tabName}"]`);
  //   selectedTabNavItem.classList.add("active");
  //   const selectedTabPane = tabsContent.querySelector(
  //     `[data-tab-panel="${tabName}"]`
  //   );
  //   selectedTabPane.classList.add("active");
  // }

  // // toast component
  // const showToast = () => {
  //   const toast = document.getElementById("toast");
  //   toast.classList.remove("hidden");

  //   setTimeout(() => {
  //     toast.classList.add("hidden");
  //   }, 3000);
  // };
  // const toastButton = document.getElementById("toast-button");
  // toastButton.addEventListener("click", () => {
  //   showToast();
  // });
})();
