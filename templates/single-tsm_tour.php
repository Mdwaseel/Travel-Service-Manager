<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $tour_id = get_the_ID();
        $book_now_url = get_option('tsm_book_now_url', '#');
        $contact_url = get_option('tsm_contact_url', '#');
        $phone_number = get_post_meta($tour_id, '_tsm_phone_number', true) ?: get_option('tsm_phone_number', '+91 93986 58775');
        $email_address = get_post_meta($tour_id, '_tsm_email', true) ?: get_option('tsm_email_address', 'chantijsp5chanti@gmail.com');
        $whatsapp_url = get_option('tsm_book_now_url', '#'); // Use book_now_url for WhatsApp as requested
        $tour_type = get_post_meta($tour_id, '_tsm_tour_type', true);
        $max_people = get_post_meta($tour_id, '_tsm_max_people', true);
        ?>
        <style>
            /* General Layout */
            body {
                background-color: #f8f9fa;
                font-family: 'Arial', sans-serif;
            }
            .tsm-tour-single {
                max-width: 1200px;
                margin: 40px auto;
                padding: 0 16px;
            }
            .tsm-tour-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 32px;
            }
            @media (max-width: 1024px) {
                .tsm-tour-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* Carousel Styles */
            .tsm-gallery-carousel {
                position: relative;
                height: 500px;
                background-color: #1a202c;
                margin-bottom: 32px;
                border-radius:10px
            }
            .tsm-gallery-carousel-container {
                position: relative;
                overflow: hidden;
                width: 100%;
                height: 100%;
            }
            .tsm-gallery-carousel-inner {
                display: flex;
                transition: transform 0.5s ease;
                width: 100%;
                height: 100%;
            }
            .tsm-gallery-carousel-slide {
                min-width: 100%;
                height: 100%;
            }
            .tsm-gallery-carousel-slide img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .tsm-gallery-carousel-overlay {
                position: absolute;
                inset: 0;
                background-color: rgba(0, 0, 0, 0.3);
            }
            .tsm-gallery-carousel-prev,
            .tsm-gallery-carousel-next {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
            
                color: white;
                
                padding: 8px;
                cursor: pointer;
                border-radius: 50%;
                z-index: 10;
                transition: background-color 0.3s ease;
                background: none !important;
                background-color: transparent !important;
                box-shadow: none !important;
                border: none !important;
            }
        

/* Ensure hover states are also overridden */
            .tsm-gallery-carousel-prev:hover,
            .tsm-gallery-carousel-next:hover {
            background: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            }

            .tsm-gallery-carousel-prev {
                left: 16px;
            }
            .tsm-gallery-carousel-next {
                right: 16px;
            }
            .tsm-gallery-carousel-indicators {
                position: absolute;
                bottom: 16px;
                right: 16px;
                display: flex;
                gap: 8px;
            }
            .tsm-gallery-carousel-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.5);
                cursor: pointer;
                transition: background-color 0.3s ease;
            }
            .tsm-gallery-carousel-indicator.active {
                background-color: #ffffff;
            }
            .tsm-gallery-carousel-counter {
                position: absolute;
                bottom: 16px;
                left: 16px;
                background-color: rgba(0, 0, 0, 0.5);
                color: white;
                padding: 4px 12px;
                border-radius: 9999px;
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            @media (max-width: 768px) {
                .tsm-gallery-carousel {
                    height: 300px;
                }
                .tsm-gallery-carousel-prev {
                    left: -33px;
                }
                .tsm-gallery-carousel-next {
                    right: -33px;
                }

            }
            @media (max-width: 480px) {
                .tsm-gallery-carousel {
                    height: 250px;
                }
                .tsm-gallery-carousel-prev {
                    left: -33px;
                }
                .tsm-gallery-carousel-next {
                    right: -33px;
                }
            }

            /* Tour Header */
            .tsm-tour-header h1 {
                font-size: 36px;
                font-weight: 700;
                color: #1a202c;
                margin-bottom: 16px;
            }
            .tsm-tour-header p {
                font-size: 18px;
                color: #4a5568;
                line-height: 1.75;
            }

            /* Quick Info Cards */
            .tsm-quick-info {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
                margin-bottom: 32px;
            }
            .tsm-quick-info-card {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 16px;
                text-align: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            .tsm-quick-info-card i {
                font-size: 32px;
                margin-bottom: 8px;
            }
            .tsm-quick-info-card .fa-calendar-alt { color: #3b82f6; }
            .tsm-quick-info-card .fa-map-pin { color: #ef4444; }
            .tsm-quick-info-card .fa-users { color: #10b981; }
            .tsm-quick-info-card .fa-clock { color: #8b5cf6; }
            .tsm-quick-info-card div:first-of-type {
                font-weight: 600;
                color: #1a202c;
            }
            .tsm-quick-info-card div:last-child {
                font-size: 14px;
                color: #6b7280;
            }
            @media (max-width: 768px) {
                .tsm-quick-info {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 480px) {
                .tsm-quick-info {
                    grid-template-columns: 1fr;
                }
            }

            /* Places Covered */
            .tsm-places-covered {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 32px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            .tsm-places-covered h2 {
                font-size: 20px;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .tsm-places-covered .badges {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .tsm-places-covered .badge {
                background-color: #e2e8f0;
                color: #1a202c;
                padding: 4px 12px;
                border-radius: 9999px;
                font-size: 14px;
            }

            /* Itinerary */
            .tsm-tour-itinerary {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 32px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            .tsm-tour-itinerary h2 {
                font-size: 20px;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .tsm-itinerary-day {
                display: flex;
                gap: 16px;
                margin-bottom: 24px;
            }
            .tsm-itinerary-day:last-child {
                margin-bottom: 0;
            }
            .tsm-itinerary-day .day-number {
                width: 40px;
                height: 40px;
                background-color: #3b82f6;
                color: #ffffff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                flex-shrink: 0;
            }
            .tsm-itinerary-day .day-content h3 {
                font-size: 18px;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 4px;
            }
            .tsm-itinerary-day .day-content span {
                font-size: 14px;
                color: #6b7280;
                margin-left: 8px;
            }
            .tsm-itinerary-day .day-content p {
                font-size: 16px;
                color: #4a5568;
            }

            /* Tour Information (Features) */
            /* Tour Information (Features) - Updated */
            .tsm-tour-featured {
                display: block !important; /* Enforce block display to prevent grid */
                background-color: #ffffff;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 32px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            .tsm-tour-featured h2 {
                font-size: 20px;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 16px;
            }
            .tsm-tour-featured .header-icon {
                margin-right: 8px;
                font-size: 20px;
                color: #3b82f6;
                vertical-align: middle;
            }
            .tsm-tour-featured .features-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            .tsm-tour-featured .feature {
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }
            .tsm-tour-featured .feature i {
                    font-size: 22px;
                    color: #3b82f6;
                    margin-top: 16px;
                    flex-shrink: 0;
                    width: 24px;
                    height: 20px;

            }
            .tsm-tour-featured .feature h4 {
                font-size: 16px;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 4px;
            }
            .tsm-tour-featured .feature p {
                font-size: 14px;
                color: #4a5568;
                line-height: 1.5;
            }
            @media (max-width: 768px) {
                .tsm-tour-featured .features-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* Guidelines (Need to Know) */
            .tsm-tour-guidelines {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 32px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            .tsm-tour-guidelines h2 {
                font-size: 20px;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 16px;
            }
            .tsm-tour-guidelines ul {
                list-style: none;
                padding: 0;
            }
            .tsm-tour-guidelines li {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 8px;
                font-size: 16px;
                color: #4a5568;
            }
            .tsm-tour-guidelines li:before {
                content: '';
                width: 8px;
                height: 8px;
                background-color: #3b82f6;
                border-radius: 50%;
                margin-top: 8px;
                flex-shrink: 0;
            }

            /* Sidebar */
            .tsm-tour-sidebar {
                position: sticky;
                top: 16px;
            }
            .tsm-pricing-card, .tsm-contact-card {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 24px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            .tsm-pricing-card h2 {
                font-size: 30px;
                font-weight: 700;
                color: #10b981;
                text-align: center;
            }
            .tsm-pricing-card p {
                font-size: 14px;
                color: #6b7280;
                text-align: center;
                margin-bottom: 16px;
            }
            .tsm-contact-card h2 {
                font-size: 20px;
                font-weight: 600;
                color: # gege;
                margin-bottom: 16px;
            }
            .tsm-contact-card a {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                color: #1a202c;
                text-decoration: none;
                margin-bottom: 12px;
                transition: background-color 0.3s ease;
            }
            .tsm-contact-card a:hover {
                background-color: #f8f9fa;
            }

            /* Buttons */
            .tsm-btn {
                display: block;
                width: 100%;
                padding: 12px;
                font-size: 18px;
                font-weight: 600;
                text-align: center;
                color: #ffffff;
                background-color: #2563eb;
                border-radius: 4px;
                text-decoration: none;
                transition: background-color 0.3s ease;
            }
            .tsm-btn:hover {
                background-color: #1d4ed8;
            }

            /* No Results */
            .tsm-no-results {
                text-align: center;
                padding: 40px;
                font-size: 18px;
                color: #4a5568;
            }
        </style>

        <div class="tsm-tour-single">
            

            <!-- Gallery Carousel -->
            <?php
            $gallery_ids = get_post_meta($tour_id, '_tsm_gallery_images', true);
            $has_images = has_post_thumbnail() || !empty($gallery_ids);
            if ($has_images) :
                ?>
                <div class="tsm-gallery-carousel">
                    <div class="tsm-gallery-carousel-container">
                        <div class="tsm-gallery-carousel-inner">
                            <?php
                            // Add featured image as the first slide if it exists
                            if (has_post_thumbnail()) : ?>
                                <div class="tsm-gallery-carousel-slide">
                                    <?php the_post_thumbnail('full', ['alt' => 'Featured Image']); ?>
                                </div>
                            <?php endif; ?>
                            <?php
                            // Add gallery images after the featured image
                            if (!empty($gallery_ids)) :
                                foreach ($gallery_ids as $id) : ?>
                                    <div class="tsm-gallery-carousel-slide">
                                        <img src="<?php echo esc_url(wp_get_attachment_url($id)); ?>" alt="Gallery Image">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="tsm-gallery-carousel-overlay"></div>
                        <button class="tsm-gallery-carousel-prev"><i class="fas fa-chevron-left"></i></button>
                        <button class="tsm-gallery-carousel-next"><i class="fas fa-chevron-right"></i></button>
                        <div class="tsm-gallery-carousel-counter">
                            <i class="fas fa-camera"></i>
                            <span id="tsm-carousel-counter">1 / <?php echo (has_post_thumbnail() ? 1 : 0) + (is_array($gallery_ids) ? count($gallery_ids) : 0); ?></span>
                        </div>
                        <div class="tsm-gallery-carousel-indicators">
                            <?php
                            $total_slides = (has_post_thumbnail() ? 1 : 0) + (is_array($gallery_ids) ? count($gallery_ids) : 0);
                            for ($index = 0; $index < $total_slides; $index++) : ?>
                                <div class="tsm-gallery-carousel-indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    const carousel = $('.tsm-gallery-carousel-inner');
                    const slides = $('.tsm-gallery-carousel-slide');
                    const indicators = $('.tsm-gallery-carousel-indicator');
                    const counter = $('#tsm-carousel-counter');
                    const slideCount = slides.length;
                    let currentIndex = 0;
                    
                    function updateCarousel() {
                        carousel.css('transform', `translateX(-${currentIndex * 100}%)`);
                        indicators.removeClass('active');
                        indicators.eq(currentIndex).addClass('active');
                        counter.text(`${currentIndex + 1} / ${slideCount}`);
                    }
                    
                    $('.tsm-gallery-carousel-next').click(function() {
                        currentIndex = (currentIndex + 1) % slideCount;
                        updateCarousel();
                    });
                    
                    $('.tsm-gallery-carousel-prev').click(function() {
                        currentIndex = (currentIndex - 1 + slideCount) % slideCount;
                        updateCarousel();
                    });
                    
                    indicators.click(function() {
                        currentIndex = parseInt($(this).data('index'));
                        updateCarousel();
                    });
                    
                    // Auto-advance carousel
                    setInterval(function() {
                        currentIndex = (currentIndex + 1) % slideCount;
                        updateCarousel();
                    }, 5000);
                });
                </script>
            <?php endif; ?>

            <!-- Tour Title and Description -->
            <div class="tsm-tour-header">
                <h1><i class="fas fa-map-marked-alt"></i> <?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            </div>



            <div class="tsm-tour-grid">
                <!-- Main Content -->
                <div class="tsm-main-content">
                    <!-- Quick Info Cards -->
                    <div class="tsm-quick-info">
                        <?php
                        $days = get_post_meta($tour_id, '_tsm_days', true);
                        $nights = get_post_meta($tour_id, '_tsm_nights', true);
                        $location = get_post_meta($tour_id, '_tsm_location', true);
                        ?>
                        <div class="tsm-quick-info-card">
                            <i class="fas fa-calendar-alt"></i>
                            <div><?php echo esc_html($days); ?> Days</div>
                            <div><?php echo esc_html($nights); ?> Nights</div>
                        </div>
                        <div class="tsm-quick-info-card">
                            <i class="fas fa-map-pin"></i>
                            <div><?php echo esc_html($location); ?></div>
                            <div>Destination</div>
                        </div>
                       <div class="tsm-quick-info-card">
                            <i class="fas fa-users"></i>
                            <div><?php echo esc_html($tour_type ?: 'Not Specified'); ?></div>
                            <div>Max <?php echo esc_html($max_people ?: 'N/A'); ?> people</div>
                        </div>
                        <div class="tsm-quick-info-card">
                            <i class="fas fa-clock"></i>
                            <div>All Inclusive</div>
                            <div>Meals & Activities</div>
                        </div>
                    </div>

                    <!-- Places Covered -->
                    <?php
                    $places = get_post_meta($tour_id, '_tsm_places_covered', true);
                    if (!empty($places)) :
                        ?>
                        <div class="tsm-places-covered">
                            <h2><i class="fas fa-map-pin"></i> Places Covered</h2>
                            <div class="badges">
                                <?php
                                $places_array = array_map('trim', explode(',', $places));
                                foreach ($places_array as $place) : ?>
                                    <span class="badge"><?php echo esc_html($place); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Itinerary -->
                    <?php
                    $itinerary = get_post_meta($tour_id, '_tsm_itinerary', true);
                    if (!empty($itinerary)) :
                        ?>
                        <div class="tsm-tour-itinerary">
                            <h2><i class="fas fa-calendar-alt"></i> Day-wise Itinerary</h2>
                            <?php foreach ($itinerary as $day) : ?>
                                <div class="tsm-itinerary-day">
                                    <div class="day-number"><?php echo esc_html($day['day_number']); ?></div>
                                    <div class="day-content">
                                        <div>
                                            <h3><?php echo esc_html($day['title']); ?>
                                                <?php if ($day['date']) : ?>
                                                    <span>(<?php echo esc_html($day['date']); ?>)</span>
                                                <?php endif; ?>
                                            </h3>
                                        </div>
                                        <p><?php echo wp_kses_post($day['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tour Information (Features) - Updated -->
                    <?php
                    $features = get_post_meta($tour_id, '_tsm_features', true);
                    if (!empty($features)) :
                        ?>
                        <div class="tsm-tour-featured">
                            <h2><i class="fas fa-info-circle"></i> Tour Information</h2>
                            <div class="features-grid">
                                <?php foreach ($features as $feature) : 
                                    // Map feature icons to match React component
                                    $icon_map = [
                                        'wifi' => 'wifi',
                                        'transportation' => 'car',
                                        'meals' => 'utensils',
                                        'travel-insurance' => 'shield-alt'
                                    ];
                                    $icon = isset($feature['icon']) && isset($icon_map[strtolower($feature['title'])]) ? $icon_map[strtolower($feature['title'])] : $feature['icon'];
                                    ?>
                                    <div class="feature">
                                        <?php if ($icon) : ?>
                                            <i class="fas fa-<?php echo esc_attr($icon); ?>"></i>
                                        <?php else : ?>
                                            <i class="fas fa-info-circle"></i>
                                        <?php endif; ?>
                                        <div>
                                            <h4><?php echo esc_html($feature['title']); ?></h4>
                                            <p><?php echo esc_html($feature['description']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Guidelines -->
                    <?php
                    $guidelines = get_post_meta($tour_id, '_tsm_guidelines', true);
                    if (!empty($guidelines)) :
                        ?>
                        <div class="tsm-tour-guidelines">
                            <h2>Need to Know</h2>
                            <ul>
                                <?php foreach ($guidelines as $guideline) : ?>
                                    <li><?php echo esc_html($guideline); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="tsm-tour-sidebar">
                    <!-- Pricing Card -->
                    <?php
                    $price = get_post_meta($tour_id, '_tsm_price_per_person', true);
                    if ($price) :
                        ?>
                        <div class="tsm-pricing-card">
                            <h2>₹<?php echo esc_html(number_format($price, 2)); ?></h2>
                            <p>per person</p>
                            <a href="<?php echo esc_url($book_now_url); ?>" class="tsm-btn">Book Now</a>
                        </div>
                    <?php endif; ?>

                    <!-- Contact Card -->
                    <div class="tsm-contact-card">
                        <h2>Need Help?</h2>
                        <a href="tel:<?php echo esc_attr($phone_number); ?>"><i class="fas fa-phone"></i> Call: <?php echo esc_html($phone_number); ?></a>
                        <a href="<?php echo esc_url($whatsapp_url); ?>"><i class="fab fa-whatsapp"></i> WhatsApp Support</a>
                        <a href="mailto:<?php echo esc_attr($email_address); ?>"><i class="fas fa-envelope"></i> Email: <?php echo esc_html($email_address); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    endwhile;
else :
    ?>
    <div class="tsm-no-results">
        <p><i class="fas fa-exclamation-circle"></i> No tour details found.</p>
    </div>
<?php
endif;

get_footer();