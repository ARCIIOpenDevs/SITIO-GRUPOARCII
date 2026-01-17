<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\App\Models\Review;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\PermissionManager;
use DateTime;

/**
 * JudgeMe Service Class
 * 
 * Handles all JudgeMe-specific functionality including import, validation,
 * and data processing for JudgeMe reviews integration with WooCommerce.
 */
class JudgeMeService
{
    // Constants for JudgeMe functionality
    private const REQUIRED_COLUMNS = [
        'title',
        'body', 
        'rating',
        'review_date',
        'reviewer_name'
    ];

    private const VALID_DATE_FORMATS = [
        'Y-m-d H:i:s T', // 2025-08-06 05:11:14 UTC
        'Y-m-d H:i:s',   // 2025-08-06 05:11:14
        'Y-m-d H:i',     // 2025-08-06 05:11
        'Y-m-d'          // 2025-08-06
    ];

    /**
     * Process Judge.me CSV import
     * 
     * Maps Judge.me export format to internal database structure
     * Required columns: title, body, rating, review_date, reviewer_name
     * Optional columns: source, curated, reviewer_email, product_id, product_handle, reply, reply_date, picture_urls, ip_address, location
     * 
     * @param array $data CSV data from Judge.me export
     * @return array Processing result with statistics
     */
    public function processImport($data)
    {
        if (empty($data)) {
            return [
                'success' => false,
                'message' => __('File is empty or invalid.', 'wp-social-ninja-pro'),
                'error_code' => 423
            ];
        }

        // Check WooCommerce installation
        if (!$this->isWooCommerceInstalled()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not installed or activated. Judge.me reviews import requires WooCommerce to be installed.', 'wp-social-ninja-pro'),
                'error_code' => 423
            ];
        }

        // Get and validate header row
        $csvHeader = array_shift($data);
        $csvHeader = array_map('esc_attr', $csvHeader);

        // Validate required columns
        $validationResult = $this->validateRequiredColumns($csvHeader);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'message' => $validationResult['message'],
                'error_code' => 423
            ];
        }

        // Process data with validation
        $processingResult = $this->processData($data, $csvHeader);

        if (empty($processingResult['mapped_data'])) {
            return $this->buildEmptyDataResponse($processingResult);
        }

        return [
            'success' => true,
            'data' => $processingResult['mapped_data'],
            'statistics' => [
                'imported' => count($processingResult['mapped_data']),
                'skipped_duplicates' => $processingResult['skipped_duplicates'],
                'skipped_invalid_products' => $processingResult['skipped_invalid_products'],
                'invalid_product_ids' => array_unique($processingResult['invalid_product_ids']),
                'total' => count($data)
            ]
        ];
    }

    /**
     * Validate required JudgeMe columns
     * 
     * @param array $csvHeader CSV header columns
     * @return array Validation result
     */
    private function validateRequiredColumns($csvHeader)
    {
        $missingColumns = array_diff(self::REQUIRED_COLUMNS, $csvHeader);
        
        if (!empty($missingColumns)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    __('Missing required columns: %s. Please ensure your CSV file contains all required Judge.me columns.', 'wp-social-ninja-pro'),
                    implode(', ', $missingColumns)
                )
            ];
        }

        return ['valid' => true];
    }

    /**
     * Process Judge.me data with validation and mapping
     * 
     * @param array $data CSV data rows
     * @param array $csvHeader CSV header columns
     * @return array Processing result with mapped data and statistics
     */
    private function processData($data, $csvHeader)
    {
        $mappedData = [];
        $skippedDuplicates = 0;
        $skippedInvalidProducts = 0;
        $invalidProductIds = [];
        $rowNumber = 1;
        
        foreach ($data as $row) {
            $rowNumber++;
            $rowData = array_combine($csvHeader, $row);
            
            // Check for duplicates before processing
            if ($this->isReviewExists($rowData)) {
                $skippedDuplicates++;
                continue;
            }
            
            // Check if WooCommerce product exists
            $productId = $rowData['product_id'] ?? '';
            if (!empty($productId) && !$this->isProductExists($productId)) {
                $skippedInvalidProducts++;
                $invalidProductIds[] = $productId;
                continue;
            }
            
            $mappedRow = $this->mapToInternalFormat($row, $csvHeader, $rowNumber);
            if ($mappedRow) {
                $mappedData[] = $mappedRow;
            }
        }

        return [
            'mapped_data' => $mappedData,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_invalid_products' => $skippedInvalidProducts,
            'invalid_product_ids' => $invalidProductIds
        ];
    }

    /**
     * Build response for empty data scenarios
     * 
     * @param array $processingResult Processing result
     * @return array Error response
     */
    private function buildEmptyDataResponse($processingResult)
    {
        $skippedDuplicates = $processingResult['skipped_duplicates'];
        $skippedInvalidProducts = $processingResult['skipped_invalid_products'];

        if ($skippedDuplicates > 0 && $skippedInvalidProducts > 0) {
            $message = sprintf(
                __('All reviews in the file are either duplicates or have invalid product IDs. %d duplicate reviews and %d reviews with invalid products were skipped.', 'wp-social-ninja-pro'),
                $skippedDuplicates,
                $skippedInvalidProducts
            );
        } elseif ($skippedDuplicates > 0) {
            $message = sprintf(
                __('All reviews in the file are duplicates. No new reviews were imported.', 'wp-social-ninja-pro')
            );
        } elseif ($skippedInvalidProducts > 0) {
            $message = sprintf(
                __('All reviews in the file have invalid product IDs. No reviews were imported.', 'wp-social-ninja-pro')
            );
        } else {
            $message = __('No valid data found in the file.', 'wp-social-ninja-pro');
        }

        return [
            'success' => false,
            'message' => $message,
            'error_code' => 423
        ];
    }

    /**
     * Check if WooCommerce is installed and activated
     * 
     * @return bool True if WooCommerce is installed and activated
     */
    private function isWooCommerceInstalled()
    {
        if (defined('WC_PLUGIN_FILE')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a WooCommerce product exists by product ID
     * 
     * @param string|int $productId The product ID to check
     * @return bool True if product exists, false otherwise
     */
    private function isProductExists($productId)
    {
        if (empty($productId)) {
            return false;
        }

        // Ensure WooCommerce is active
        if (!$this->isWooCommerceInstalled()) {
            return false;
        }

        // Check if the product exists using WooCommerce functions
        $product = wc_get_product($productId);
        
        if ($product && $product->get_id()) {
            return true;
        }

        // Also check by post type in case wc_get_product fails
        $post = get_post($productId);
        if ($post && $post->post_type === 'product' && $post->post_status === 'publish') {
            return true;
        }

        return false;
    }

    /**
     * Check if a Judge.me review already exists to prevent duplicates
     * 
     * @param array $rowData Judge.me row data
     * @return bool True if review exists, false otherwise
     */
    private function isReviewExists($rowData)
    {
        $productId = $rowData['product_id'] ?? '';
        $reviewerName = $rowData['reviewer_name'] ?? '';
        $reviewDate = $this->formatDate($rowData['review_date'] ?? '');
        $reviewText = $rowData['body'] ?? '';

        // Check for exact match on all criteria
        $existingReview = Review::where('platform_name', 'woocommerce')
            ->where('source_id', $productId)
            ->where('reviewer_name', $reviewerName)
            ->where('reviewer_text', $reviewText)
            ->where('review_time', $reviewDate)
            ->first();

        return $existingReview !== null;
    }

    /**
     * Map Judge.me CSV row to internal database format
     * 
     * @param array $row CSV row data
     * @param array $csvHeader CSV header columns
     * @param int $rowNumber Row number for error reporting
     * @return array|null Mapped data or null if validation fails
     */
    private function mapToInternalFormat($row, $csvHeader, $rowNumber = 0)
    {
        // Create associative array from row data
        $rowData = array_combine($csvHeader, $row);
        
        // Validate required fields
        if (empty($rowData['reviewer_name'])) {
            return null; // Skip this row
        }
    
        $rating = Arr::get($rowData, 'rating', 0);
        
        // Map Judge.me fields to internal format
        $mappedData = [
            'platform_name' => 'woocommerce',
            'source_id' => $rowData['product_id'] ?? '',
            'review_id' => $this->generateReviewId($rowData),
            'category' => $rowData['product_handle'] ?? '',
            'review_title' => $rowData['title'] ?? '',
            'reviewer_name' => $rowData['reviewer_name'] ?? '',
            'reviewer_url' => '', // Judge.me doesn't provide reviewer URL
            'reviewer_img' => '', // Judge.me doesn't provide reviewer image
            'reviewer_text' => $rowData['body'] ?? '',
            'review_time' => $this->formatDate($rowData['review_date'] ?? ''),
            'rating' => $rating,
            'review_approved' => 1,
            'recommendation_type' => '',
            'fields' => $this->mapFields($rowData)
        ];

        return $mappedData;
    }

    /**
     * Generate a unique review ID for Judge.me reviews
     * 
     * @param array $rowData Judge.me row data
     * @return string Unique review ID
     */
    private function generateReviewId($rowData)
    {
        $productId = $rowData['product_id'] ?? '';
        $reviewerName = $rowData['reviewer_name'] ?? '';
        $reviewDate = $rowData['review_date'] ?? '';
        
        // Create a unique ID based on product, reviewer, and date
        $uniqueString = $productId . '_' . $reviewerName . '_' . $reviewDate;
        return 'judge_me_' . md5($uniqueString);
    }

    /**
     * Map Judge.me specific fields to JSON fields column
     * 
     * @param array $rowData Judge.me row data
     * @return string JSON encoded fields
     */
    private function mapFields($rowData)
    {
        $fields = [
            'reviewer_email' => $rowData['reviewer_email'] ?? '',
            'source' => $rowData['source'] ?? 'judge-me',
            'curated' => $rowData['curated'] ?? '',
            'product_id' => $rowData['product_id'] ?? '',
            'product_handle' => $rowData['product_handle'] ?? '',
            'reply' => $rowData['reply'] ?? '',
            'reply_date' => $rowData['reply_date'] ?? '',
            'picture_urls' => $rowData['picture_urls'] ?? '',
            'ip_address' => $rowData['ip_address'] ?? '',
            'location' => $rowData['location'] ?? '',
            'imported_from' => 'judge-me'
        ];
        
        return json_encode($fields);
    }

    /**
     * Get business info for WooCommerce Judge.me reviews
     *
     * @param array $rows Review rows
     * @param string $type Info type to retrieve
     * @return array Business info
     */
    public function getWooCommerceBusinessInfo($rows, $type)
    {
        $dataFields     = json_decode(Arr::get($rows, '0.fields', '{}'), true);
        $source_id      = Arr::get($dataFields, 'product_id');
        $imported_from  = Arr::get($dataFields, 'imported_from', '');
        $product_handle = Arr::get($dataFields, 'product_handle', '');

        if (!empty($source_id) && defined('WC_VERSION')) {
            $product_handle = get_the_title($source_id);
        }

        $dataSource = [
            'handle'      => $product_handle,
            'source_id'   => $source_id,
            'is_imported' => true,
        ];

        if ($imported_from === 'judge-me' && $source_id) {
            return Review::getInternalBusinessInfo($type, $dataSource);
        }

        return [];
    }

    /**
     * Format Judge.me date string to MySQL datetime format
     * 
     * Handles various date formats from Judge.me exports:
     * - Y-m-d H:i:s T (2025-08-06 05:11:14 UTC)
     * - Y-m-d H:i:s (2025-08-06 05:11:14)
     * - Y-m-d H:i (2025-08-06 05:11)
     * - Y-m-d (2025-08-06)
     * 
     * @param string $dateString Date string from Judge.me
     * @return string Formatted date string for MySQL
     */
    private function formatDate($dateString)
    {
        if (empty($dateString)) {
            return date('Y-m-d H:i:s');
        }

        // Handle different date formats from Judge.me
        foreach (self::VALID_DATE_FORMATS as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // If no format matches, try strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return date('Y-m-d H:i:s');
    }
}
