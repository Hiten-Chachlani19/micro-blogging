<?php

namespace App\Services\Posts;

class ContentParser
{
   public function extractMentions(string $body): array
   {
        preg_match_all('/@([A-Za-z0-9_]{1,30})/', $body, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));  
   }

   public function extractHashtags(string $body): array
   {
        preg_match_all('/#([A-Za-z0-9_]{1,50})/', $body, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1]))); 
   }
}
