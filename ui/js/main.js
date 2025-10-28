document.addEventListener('DOMContentLoaded', () => {

    function initApp() {
        // --- Theme Toggler ---
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        
        const updateThemeIcons = () => {
            if (!themeToggle) return;
            const moonIcon = themeToggle.querySelector('.fa-moon');
            const sunIcon = themeToggle.querySelector('.fa-sun');
            if (htmlElement.getAttribute('data-theme') === 'dark') {
                moonIcon.style.display = 'none';
                sunIcon.style.display = 'inline-block';
            } else {
                moonIcon.style.display = 'inline-block';
                sunIcon.style.display = 'none';
            }
        };

        const switchTheme = () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                htmlElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcons();
        };

        const setInitialTheme = () => {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
                htmlElement.setAttribute('data-theme', 'dark');
            } else {
                htmlElement.setAttribute('data-theme', 'light');
            }
            updateThemeIcons();
        };
        
        if (themeToggle) {
            themeToggle.addEventListener('click', switchTheme);
        }
        setInitialTheme();

        // --- Live Weather Conditions Bar ---
        const conditionsBar = document.getElementById('live-conditions');
        if (conditionsBar) {
            const cities = [
                { name: 'Las Cruces, NM', stationId: 'KLRU' },
                { name: 'El Paso, TX', stationId: 'KELP' },
                { name: 'White Sands, NM', stationId: 'KOWI' }
            ];
            
            const fetchAllWeather = async () => {
                const weatherPromises = cities.map(city =>
                    fetch(`https://api.weather.gov/stations/${city.stationId}/observations/latest`)
                        .then(response => { if (!response.ok) return null; return response.json(); })
                        .then(data => {
                            if (!data || !data.properties || data.properties.temperature.value === null) return null;
                            const tempC = data.properties.temperature.value;
                            const tempF = Math.round((tempC * 9/5) + 32);
                            const description = data.properties.textDescription;
                            return { name: city.name, conditions: `${tempF}°F, ${description}` };
                        })
                        .catch(() => null)
                );
            
                try {
                    const results = await Promise.all(weatherPromises);
                    const validResults = results.filter(r => r !== null); 
                    if (validResults.length === 0) throw new Error("All weather API requests failed.");
                    startWeatherCycle(validResults);
                } catch (error) {
                    console.error("Failed to fetch weather data:", error);
                    conditionsBar.textContent = "Live regional conditions are currently unavailable.";
                    conditionsBar.classList.add('error');
                }
            };
            
            let currentCityIndex = 0;
            const startWeatherCycle = (weatherData) => {
                const displayNextCity = () => {
                    if (weatherData.length === 0) return;
                    const city = weatherData[currentCityIndex];
                    conditionsBar.style.opacity = 0;
                    setTimeout(() => {
                        conditionsBar.innerHTML = `<strong>${city.name}:</strong> ${city.conditions}`;
                        conditionsBar.style.opacity = 1;
                    }, 500);
                    currentCityIndex = (currentCityIndex + 1) % weatherData.length;
                };
                displayNextCity();
                setInterval(displayNextCity, 5000);
            };
            
            fetchAllWeather();
        }

        // --- Toast Notifications ---
        const toastContainer = document.getElementById('toast-container');
        const showToast = (message) => {
            if (!toastContainer) return;
            const toast = document.createElement('div');
            toast.className = 'toast show';
            toast.textContent = message;
            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('show');
                toast.classList.add('hide');
                toast.addEventListener('animationend', () => toast.remove());
            }, 3000);
        };

        // --- Modal Logic ---
        const openModal = (modal) => modal.classList.add('visible');
        const closeModal = (modal) => modal.classList.remove('visible');

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            if(!modal) return;
            const closeButton = modal.querySelector('.modal-close');
            if (closeButton) {
                closeButton.addEventListener('click', () => closeModal(modal));
            }
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal(modal);
            });
        });
        
        const accountModal = document.getElementById('account-modal');
        const accountTrigger = document.getElementById('account-trigger');
        if (accountTrigger && accountModal) {
            accountTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                openModal(accountModal);
            });
        }
        
        // Check if we need to open the login modal on page load
        if (localStorage.getItem('openLoginModal') === 'true' && accountModal) {
            openModal(accountModal);
            localStorage.removeItem('openLoginModal'); // Clear the flag
        }

        const contactModal = document.getElementById('contact-modal');
        const contactTrigger = document.getElementById('contact-trigger');
        if (contactTrigger && contactModal) {
            contactTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                openModal(contactModal);
            });
        }

        const handleFormSubmit = (event) => {
            event.preventDefault();
            const form = event.target;
            const parentModal = form.closest('.modal-overlay');

            if (parentModal) closeModal(parentModal);
            
            if (form.closest('#contact-modal')) {
                showToast('Message sent successfully!');
            } else {
                showToast('Thank you for subscribing!');
            }
            form.reset();
        };

        document.querySelectorAll('.newsletter-form, .modal-form').forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
        });

        // --- Cookie Banner ---
        const cookieBanner = document.getElementById('cookie-banner');
        if (cookieBanner) {
            const acceptCookiesBtn = document.getElementById('accept-cookies');
            if (!localStorage.getItem('cookiesAccepted')) {
                setTimeout(() => cookieBanner.classList.add('visible'), 2500);
            }
            if (acceptCookiesBtn) {
                acceptCookiesBtn.addEventListener('click', () => {
                    cookieBanner.classList.remove('visible');
                    localStorage.setItem('cookiesAccepted', 'true');
                });
            }
        }

        // --- Back to Top Button ---
        const backToTopBtn = document.getElementById('back-to-top');
        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 400) {
                    backToTopBtn.classList.add('visible');
                } else {
                    backToTopBtn.classList.remove('visible');
                }
            }, { passive: true });
            backToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }

        // --- Scroll Animations ---
        const scrollAnimatedElements = document.querySelectorAll('.fade-in-on-scroll');
        if (scrollAnimatedElements.length > 0) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            scrollAnimatedElements.forEach(el => observer.observe(el));
        }
        
        // --- Guide Filter Logic ---
        const filterContainer = document.querySelector('.guide-filters');
        if (filterContainer) {
            const guideCards = document.querySelectorAll('.guide-card');
            filterContainer.addEventListener('click', (e) => {
                const target = e.target;
                if (!target.classList.contains('filter-btn')) return;

                filterContainer.querySelector('.active').classList.remove('active');
                target.classList.add('active');

                const filterValue = target.dataset.filter;

                guideCards.forEach(card => {
                    const cardCategory = card.dataset.category;
                    const shouldShow = filterValue === 'all' || filterValue === cardCategory;
                    
                    if (shouldShow) {
                        card.classList.remove('hide');
                        // We re-add display style because 'hide' class sets it to none
                        card.style.display = ''; 
                    } else {
                        card.classList.add('hide');
                        // Use timeout to allow animation before setting display none
                        setTimeout(() => {
                           if(card.classList.contains('hide')) card.style.display = 'none';
                        }, 300);
                    }
                });
            });
        }

        // --- FAQ Accordion ---
        const faqItems = document.querySelectorAll('.faq-item');
        if (faqItems.length > 0) {
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                question.addEventListener('click', () => {
                    const isOpening = !item.classList.contains('active');
                    
                    // Close all other items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                            otherItem.querySelector('.faq-answer').setAttribute('hidden', '');
                        }
                    });

                    // Toggle the clicked item
                    if (isOpening) {
                        item.classList.add('active');
                        question.setAttribute('aria-expanded', 'true');
                        answer.removeAttribute('hidden');
                    } else {
                        item.classList.remove('active');
                        question.setAttribute('aria-expanded', 'false');
                        answer.setAttribute('hidden', '');
                    }
                });
            });
        }
    }
    
    // --- Page Load Animation ---
    const topLoaderBar = document.getElementById('top-loader-bar');
    const shimmerLoader = document.getElementById('shimmer-loader');
    const contentContainer = document.querySelector('.bento-container, .stories-container, .guides-container, .events-container, .membership-container, .story-container');
    const mainElement = document.querySelector('main');
    
    if (mainElement) {
        mainElement.style.visibility = 'visible';
    }

    setTimeout(() => {
        if(topLoaderBar) topLoaderBar.style.transform = 'scaleX(1)';
    }, 10);

    setTimeout(() => {
        if(topLoaderBar) topLoaderBar.style.opacity = '0';
        
        if (shimmerLoader) {
            shimmerLoader.style.opacity = '0';
            shimmerLoader.addEventListener('transitionend', () => {
                shimmerLoader.style.display = 'none';
                if (contentContainer) {
                    contentContainer.classList.remove('hidden');
                    contentContainer.style.opacity = '0';
                    contentContainer.style.animation = 'fadeInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
                }
            }, { once: true });
        } else if (contentContainer) {
            // If there's no shimmer loader, just fade in the content
            contentContainer.classList.remove('hidden');
            contentContainer.style.opacity = '0';
            contentContainer.style.animation = 'fadeInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
        }
        
        if(topLoaderBar) {
            topLoaderBar.addEventListener('transitionend', () => {
                 if(topLoaderBar) topLoaderBar.style.display = 'none';
            }, { once: true });
        }

        initApp();

    }, 1000); // Reduced delay for a faster feel
});
