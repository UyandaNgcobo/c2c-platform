<?php 
    // Start the session so we can read session variables
    session_start();
    
    // Bring in the database connection!
    include 'includes/db.php';

    // Check if the user is logged in AT ALL
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?msg=login_required");
        exit();
    }

    // Block banned users from accessing the sell form
    if (isset($_SESSION['is_banned']) && $_SESSION['is_banned'] == 1) {
        header("Location: account.php?msg=account_restricted");
        exit();
    }

    include 'includes/header.php';
?>
<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content-feed">
        <div class="form-container">
            <h2>List an Item for Sale</h2>
            <p class="form-subtitle">Fill out the details below to deploy your product to the marketplace.</p>

            <div id="form-error-banner" class="error-banner">
                ⚠️ Please fill out all required fields highlighted in red below.
            </div>

            <form id="sell-form" action="process-listing.php" method="POST" enctype="multipart/form-data" class="sell-form" novalidate>
                
                <div class="form-section">
                    <h3>1. Core Information</h3>
                    
                    <div class="input-group">
                        <label for="category">Category*</label>
                        <div class="input-group">
    <label>Categories (Select all that apply)*</label>
    <div class="checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; max-height: 200px; overflow-y: auto;">
        <?php
        $cat_query = "SELECT id, name FROM categories ORDER BY name ASC";
        $cat_result = $conn->query($cat_query);
        
        if ($cat_result && $cat_result->num_rows > 0) {
            while($row = $cat_result->fetch_assoc()) {
                // IMPORTANT: Note the name="categories[]" with the brackets
                echo "<label style='display: flex; align-items: center; gap: 8px; cursor: pointer;'>
                        <input type='checkbox' name='categories[]' value='" . $row['id'] . "'> 
                        " . htmlspecialchars($row['name']) . "
                      </label>";
            }
        }
        ?>
    </div>
</div>
                    </div>

                    <div class="input-group">
                        <label for="title">Product Title*</label>
                        <input type="text" id="title" name="title" placeholder="e.g., PlayStation 5 Console Slim" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="brand">Brand*</label>
                            <input type="text" id="brand" name="brand" placeholder="e.g., Sony" required>
                        </div>
                        <div class="input-group">
                            <label for="identifier">Unique Identifier (Model / ISBN / Serial)</label>
                            <input type="text" id="identifier" name="identifier" placeholder="e.g., CFI-2016A">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="condition">Item Condition*</label>
                            <select id="condition" name="condition" required>
                                <option value="" disabled selected>Select condition...</option>
                                <option value="New">New (Sealed/Unused)</option>
                                <option value="Refurbished">Refurbished</option>
                                <option value="Secondhand">Secondhand (Lightly Used)</option>
                                <option value="Well Worn">Well Worn / For Parts</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="quantity">Quantity Available*</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>2. Product Images</h3>
                    <p class="section-help">To ensure buyer trust, we require 5 specific angles. You may optionally add up to 5 extra photos.</p>

                    <div class="input-group">
                        <label>Required Angles*</label>
                        <div class="required-images-grid">
                            
                            <div class="single-upload-wrapper">
                                <div class="upload-dropzone-sm" id="dz-main" onclick="document.getElementById('in-main').click()">
                                    <span class="upload-icon-sm">📸</span><p>Main</p>
                                    <input type="file" id="in-main" name="img_main" accept="image/*" required hidden onchange="handleSingleUpload(this, 'pr-main', 'dz-main')">
                                </div>
                                <div id="pr-main" class="preview-card hidden-fields"></div>
                            </div>

                            <div class="single-upload-wrapper">
                                <div class="upload-dropzone-sm" id="dz-front" onclick="document.getElementById('in-front').click()">
                                    <span class="upload-icon-sm">📦</span><p>Front</p>
                                    <input type="file" id="in-front" name="img_front" accept="image/*" required hidden onchange="handleSingleUpload(this, 'pr-front', 'dz-front')">
                                </div>
                                <div id="pr-front" class="preview-card hidden-fields"></div>
                            </div>

                            <div class="single-upload-wrapper">
                                <div class="upload-dropzone-sm" id="dz-back" onclick="document.getElementById('in-back').click()">
                                    <span class="upload-icon-sm">🔄</span><p>Back</p>
                                    <input type="file" id="in-back" name="img_back" accept="image/*" required hidden onchange="handleSingleUpload(this, 'pr-back', 'dz-back')">
                                </div>
                                <div id="pr-back" class="preview-card hidden-fields"></div>
                            </div>

                            <div class="single-upload-wrapper">
                                <div class="upload-dropzone-sm" id="dz-side" onclick="document.getElementById('in-side').click()">
                                    <span class="upload-icon-sm">📐</span><p>Side</p>
                                    <input type="file" id="in-side" name="img_side" accept="image/*" required hidden onchange="handleSingleUpload(this, 'pr-side', 'dz-side')">
                                </div>
                                <div id="pr-side" class="preview-card hidden-fields"></div>
                            </div>

                            <div class="single-upload-wrapper">
                                <div class="upload-dropzone-sm" id="dz-detail" onclick="document.getElementById('in-detail').click()">
                                    <span class="upload-icon-sm">🔍</span><p>Detail</p>
                                    <input type="file" id="in-detail" name="img_detail" accept="image/*" required hidden onchange="handleSingleUpload(this, 'pr-detail', 'dz-detail')">
                                </div>
                                <div id="pr-detail" class="preview-card hidden-fields"></div>
                            </div>

                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #eee; margin: 1.5rem 0;">

                    <div class="input-group">
                        <label>Additional Images (Optional - Max 5)</label>
                        <div class="upload-dropzone" id="gallery-dropzone" onclick="document.getElementById('gallery-input').click()">
                            <span class="upload-icon">➕</span>
                            <p>Click to add up to 5 extra photos</p>
                            <input type="file" id="gallery-input" name="gallery[]" accept="image/*" multiple hidden onchange="handleGallery(this)">
                        </div>
                        <div id="gallery-preview-container" class="preview-grid"></div>
                    </div>
                </div>

                <div class="input-group">
                    <label for="listing-type">How do you want to sell this item?*</label>
                    <select id="listing-type" name="listing_type" required onchange="togglePricingFields()">
                        <option value="fixed" selected>Fixed Price Only (Instant Purchase)</option>
                        <option value="auction">Auction Only (Bidding Process)</option>
                    </select>
                </div>

                <div id="fixed-price-box" class="price-field">
                    <div class="input-group">
                        <label for="buy-now-price">Instant "Buy Now" Price (R)*</label>
                        <input type="number" id="buy-now-price" name="buy_now_price" min="1" placeholder="e.g., 8500" required>
                    </div>
                </div>

                <div id="auction-price-box" class="price-field hidden-fields">
                    <div class="form-row">
                        <div class="input-group">
                            <label for="starting-bid">Starting Bid (R)*</label>
                            <input type="number" id="starting-bid" name="starting_bid" min="1" placeholder="e.g., 4000">
                        </div>
                        <div class="input-group">
                            <label for="auction-duration">Auction Duration*</label>
                            <select id="auction-duration" name="auction_duration">
                                <option value="3">3 Days</option>
                                <option value="5">5 Days</option>
                                <option value="7" selected>7 Days</option>
                                <option value="10">10 Days</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>4. Description</h3>
                    <div class="input-group">
                        <label for="description">Tell buyers more about the item*</label>
                        <textarea id="description" name="description" rows="5" placeholder="Mention features, faults, reasons for selling..." required></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-listing-btn">Publish Listing</button>
            </form>
        </div>
    </main>
</div>

<script>
    function togglePricingFields() {
        const type = document.getElementById('listing-type').value;
        const fixedBox = document.getElementById('fixed-price-box');
        const auctionBox = document.getElementById('auction-price-box');
        const buyPriceInput = document.getElementById('buy-now-price');
        const startBidInput = document.getElementById('starting-bid');

        if (type === 'fixed') {
            fixedBox.classList.remove('hidden-fields');
            auctionBox.classList.add('hidden-fields');
            
            buyPriceInput.required = true;
            startBidInput.required = false;
        } else {
            fixedBox.classList.add('hidden-fields');
            auctionBox.classList.remove('hidden-fields');
            
            buyPriceInput.required = false;
            startBidInput.required = true;
        }
    }

    // --- REUSABLE REQUIRED IMAGE LOGIC ---
    function handleSingleUpload(input, previewId, dropzoneId) {
        const container = document.getElementById(previewId);
        const dropzone = document.getElementById(dropzoneId);
        
        container.innerHTML = '';
        dropzone.classList.remove('error-highlight');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                container.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-btn" title="Remove" onclick="removeSingleImage('${input.id}', '${previewId}', '${dropzoneId}')">×</button>
                `;
                container.classList.remove('hidden-fields');
                dropzone.classList.add('hidden-fields'); 
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeSingleImage(inputId, previewId, dropzoneId) {
        document.getElementById(inputId).value = ''; 
        document.getElementById(previewId).innerHTML = ''; 
        document.getElementById(previewId).classList.add('hidden-fields');
        document.getElementById(dropzoneId).classList.remove('hidden-fields'); 
    }

    // --- OPTIONAL GALLERY LOGIC (Max 5) ---
    const galleryInput = document.getElementById('gallery-input');
    const galleryContainer = document.getElementById('gallery-preview-container');
    let dt = new DataTransfer(); 

    function handleGallery(input) {
        const files = input.files;
        
        if (dt.items.length + files.length > 5) {
            alert("You can only upload a maximum of 5 additional images.");
            return;
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            dt.items.add(file); 
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-card';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Gallery Image">
                    <button type="button" class="remove-btn" onclick="removeGalleryImage('${file.name}', this)">×</button>
                `;
                galleryContainer.appendChild(div);
            }
            reader.readAsDataURL(file);
        }
        
        galleryInput.files = dt.files;
    }

    function removeGalleryImage(fileName, buttonElement) {
        buttonElement.closest('.preview-card').remove();
        
        for (let i = 0; i < dt.items.length; i++) {
            if (dt.items[i].getAsFile().name === fileName) {
                dt.items.remove(i);
                break;
            }
        }
        
        galleryInput.files = dt.files;
    }

    // FORM VALIDATION INTERCEPTOR 
    document.getElementById('sell-form').addEventListener('submit', function(event) {
        let isValid = true;
        let firstInvalidElement = null;
        
        document.querySelectorAll('.error-highlight').forEach(el => el.classList.remove('error-highlight'));
        const banner = document.getElementById('form-error-banner');
        banner.style.display = 'none';

        const requiredFields = this.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value || field.value.trim() === '') {
                isValid = false;
                let targetToHighlight = field;

                if (field.type === 'file') {
                    targetToHighlight = field.parentElement.querySelector('.upload-dropzone-sm, .upload-dropzone');
                }

                if (targetToHighlight) {
                    targetToHighlight.classList.add('error-highlight');
                }

                if (!firstInvalidElement) {
                    firstInvalidElement = targetToHighlight || field;
                }
            }
        });

        if (!isValid) {
            event.preventDefault(); 
            banner.style.display = 'block'; 
            firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>