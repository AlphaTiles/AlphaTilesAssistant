<?php

namespace App\Services;

class ParseWordByInventoryService
{
    /**
     * Greedily parses a word using inventory tokens.
     *
     * @param array<string, mixed> $inventoryMap Map of inventory token => payload to return on match.
     * @return array{items: array<int, mixed>, fully_parsed: bool}
     */
    public function handle(
        string $word,
        array $inventoryMap,
        int $maxRawChunkLength,
        ?string $placeholderCharacter = null
    ): array {
        $matchedItems = [];
        $fullyParsed = true;
        $wordLength = mb_strlen($word);

        for ($i = 0; $i < $wordLength; ) {
            $bestItem = null;
            $bestRawChunkLength = 0;

            for ($chunkLength = 1; $chunkLength <= $maxRawChunkLength; $chunkLength++) {
                if ($i + $chunkLength > $wordLength) {
                    break;
                }

                $chunk = mb_substr($word, $i, $chunkLength);

                $candidates = [$chunk];
                if (!empty($placeholderCharacter)) {
                    $candidates[] = $placeholderCharacter . $chunk;
                    $candidates[] = $chunk . $placeholderCharacter;
                    $candidates[] = $placeholderCharacter . $chunk . $placeholderCharacter;
                }

                foreach ($candidates as $candidate) {
                    if (isset($inventoryMap[$candidate])) {
                        $bestItem = $inventoryMap[$candidate];
                        $bestRawChunkLength = $chunkLength;
                        break;
                    }
                }
            }

            if ($bestItem === null) {
                $fullyParsed = false;
                $i++;
                continue;
            }

            $matchedItems[] = $bestItem;
            $i += $bestRawChunkLength;
        }

        return [
            'items' => $matchedItems,
            'fully_parsed' => $fullyParsed,
        ];
    }
}
