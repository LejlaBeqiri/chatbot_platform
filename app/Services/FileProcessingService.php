<?php

namespace App\Services;

use App\Jobs\ProcessPdfEmbeddings;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileProcessingService
{
    private $chunkSize = 800;

    private $overlap = 160;

    public function processPdfFile(Media $media, int $knowledgeBaseId, string $apiKey): void
    {
        try {

            $parser   = new Parser;
            $filePath = $media->getPath();
            // Parse PDF file
            $pdf         = $parser->parseFile($filePath);
            $textContent = $pdf->getText();

            // Clean and encode text content
            $textContent = $this->cleanTextContent($textContent);

            if (empty($textContent)) {
                Log::warning("No text content extracted from PDF: {$filePath} after normalization. Skipping embedding.");

                return;
            }

            $chunksForEmbedding = [];

            // Split text into overlapping chunks
            
            for ($i = 0; $i < strlen($textContent); $i += ($this->chunkSize - $this->overlap)) {
                $chunkContent = trim(substr($textContent, $i, $this->chunkSize));

                // Clean each chunk individually
                $chunkContent = $this->cleanTextContent($chunkContent);

                // Only add if the chunk is not empty after trimming and cleaning
                if (! empty($chunkContent)) {
                    $chunksForEmbedding[] = [
                        ['role' => 'assistant', 'content' => $chunkContent],
                    ];
                }
            }

            // Process all chunks together
            if (! empty($chunksForEmbedding)) {
                Log::info("Dispatching job with " . count($chunksForEmbedding) . " chunks");
                ProcessPdfEmbeddings::dispatch($knowledgeBaseId, $chunksForEmbedding, $apiKey, $media);
            }
        } catch (\Exception $e) {
            Log::error("Error processing PDF file: " . $e->getMessage(), [
                'file'  => $media->file_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Clean and normalize text content
     */
    private function cleanTextContent(string $text): string
    {
        // Convert to UTF-8 if not already
        if (!mb_check_encoding($text, 'UTF-8')) {
            $detectedEncoding = mb_detect_encoding($text, ['UTF-8', 'ASCII', 'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-15', 'Windows-1252'], true);
            $text = mb_convert_encoding($text, 'UTF-8', $detectedEncoding ?: 'Windows-1252');
        }

        // Remove any invalid UTF-8 sequences
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);

        // Normalize whitespace
        $text = preg_replace('/\r\n|\r|\n/', ' ', $text); // Replace newlines with spaces
        $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with single space
        $text = trim($text); // Remove leading/trailing whitespace

        // Remove any non-printable characters except spaces
        $text = preg_replace('/[^\P{C}\s]/u', '', $text);

        return $text;
    }
}
