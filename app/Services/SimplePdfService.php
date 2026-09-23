<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SimplePdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const LEFT = 48;
    private const TOP = 674;
    private const BOTTOM = 62;
    private const CONTENT_WIDTH = 499;

    public function download(string $filename, string $title, array $sections): Response
    {
        return response($this->make($title, $sections), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->safeFilename($filename).'"',
        ]);
    }

    public function make(string $title, array $sections): string
    {
        $title = $this->normalizeText($title);
        $pages = [$this->pageHeader($title)];
        $pageIndex = 0;
        $y = self::TOP;

        if ($sections === []) {
            $this->addParagraph($pages, $pageIndex, $y, $title, 'Aucune donnee disponible.', 11, false, 0);
        }

        foreach ($sections as $section) {
            $this->addSection(
                $pages,
                $pageIndex,
                $y,
                $title,
                (string) ($section['heading'] ?? ''),
                (array) ($section['lines'] ?? [])
            );
        }

        $totalPages = count($pages);

        foreach ($pages as $index => $commands) {
            $pages[$index] = array_merge($commands, $this->pageFooter($index + 1, $totalPages));
        }

        return $this->buildPdf($pages);
    }

    private function addSection(
        array &$pages,
        int &$pageIndex,
        int &$y,
        string $title,
        string $heading,
        array $lines
    ): void {
        if ($y < self::BOTTOM + 88) {
            $this->newPage($pages, $pageIndex, $y, $title);
        }

        $heading = $heading !== '' ? $heading : 'Section';
        $pages[$pageIndex][] = $this->rectCommand(self::LEFT - 12, $y - 8, self::CONTENT_WIDTH + 24, 30, [0.93, 0.98, 0.96]);
        $pages[$pageIndex][] = $this->rectCommand(self::LEFT - 12, $y - 8, 5, 30, [0.09, 0.74, 0.51]);
        $pages[$pageIndex][] = $this->textCommand($heading, 13, true, $y + 2, self::LEFT, [0.06, 0.12, 0.23]);
        $y -= 34;

        foreach ($lines as $line) {
            $this->addParagraph($pages, $pageIndex, $y, $title, (string) $line, 11, false, 7);
        }

        $y -= 12;
    }

    private function addParagraph(
        array &$pages,
        int &$pageIndex,
        int &$y,
        string $title,
        string $text,
        int $size,
        bool $bold,
        int $gapAfter,
        int $x = self::LEFT,
        array $color = [0.25, 0.34, 0.46]
    ): void {
        foreach (preg_split('/\R/', $text) ?: [''] as $paragraphLine) {
            $wrappedLines = $this->wrapText($paragraphLine, $this->maxCharsForSize($size));

            foreach ($wrappedLines as $line) {
                $lineHeight = $size + 5;

                if ($y < self::BOTTOM + $lineHeight) {
                    $this->newPage($pages, $pageIndex, $y, $title);
                }

                $pages[$pageIndex][] = $this->textCommand($line, $size, $bold, $y, $x, $color);
                $y -= $lineHeight;
            }
        }

        $y -= $gapAfter;
    }

    private function wrapText(string $text, int $maxChars): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $this->normalizeText($text)) ?? '');

        if ($text === '') {
            return [''];
        }

        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            if (strlen($word) > $maxChars) {
                foreach (str_split($word, $maxChars) as $piece) {
                    if ($current !== '') {
                        $lines[] = $current;
                        $current = '';
                    }

                    $lines[] = $piece;
                }

                continue;
            }

            $candidate = $current === '' ? $word : $current.' '.$word;

            if (strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function maxCharsForSize(int $size): int
    {
        return match (true) {
            $size >= 18 => 54,
            $size >= 14 => 68,
            default => 92,
        };
    }

    private function pageHeader(string $title): array
    {
        return [
            $this->rectCommand(0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, [0.96, 0.98, 1.00]),
            $this->rectCommand(0, 716, self::PAGE_WIDTH, 126, [0.13, 0.24, 0.44]),
            $this->rectCommand(0, 716, self::PAGE_WIDTH, 7, [0.09, 0.74, 0.51]),
            $this->leafIconCommand(self::LEFT, 796),
            $this->textCommand('CarbonAI', 12, true, 801, 72, [1.00, 1.00, 1.00]),
            $this->textCommand($this->shortenText($title, 72), 22, true, 764, self::LEFT, [1.00, 1.00, 1.00]),
            $this->textCommand('Genere le '.now()->format('d/m/Y H:i'), 9, false, 740, self::LEFT, [0.79, 0.87, 0.97]),
        ];
    }

    private function pageFooter(int $pageNumber, int $totalPages): array
    {
        return [
            $this->lineCommand(self::LEFT, 42, self::PAGE_WIDTH - self::LEFT, 42, [0.84, 0.88, 0.94]),
            $this->textCommand('CarbonAI - Rapport exporte', 8, false, 28, self::LEFT, [0.45, 0.53, 0.65]),
            $this->textCommand('Page '.$pageNumber.' / '.$totalPages, 8, false, 28, 500, [0.45, 0.53, 0.65]),
        ];
    }

    private function newPage(array &$pages, int &$pageIndex, int &$y, string $title): void
    {
        $pages[] = $this->pageHeader($title);
        $pageIndex++;
        $y = self::TOP;
    }

    private function textCommand(string $text, int $size, bool $bold, int $y, int $x = self::LEFT, array $color = [0, 0, 0]): string
    {
        $font = $bold ? 'F2' : 'F1';

        return sprintf(
            "BT /%s %d Tf %s rg %.2F %.2F Td (%s) Tj ET",
            $font,
            $size,
            $this->rgb($color),
            $x,
            $y,
            $this->escapePdfText($text)
        );
    }

    private function rectCommand(int $x, int $y, int $width, int $height, array $color): string
    {
        return sprintf(
            'q %s rg %.2F %.2F %.2F %.2F re f Q',
            $this->rgb($color),
            $x,
            $y,
            $width,
            $height
        );
    }

    private function lineCommand(int $x1, int $y1, int $x2, int $y2, array $color): string
    {
        return sprintf(
            'q %s RG 0.60 w %.2F %.2F m %.2F %.2F l S Q',
            $this->rgb($color),
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    private function leafIconCommand(int $x, int $y): string
    {
        return sprintf(
            'q %s rg %.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c f Q q 1.000 1.000 1.000 RG 1.15 w 1 J %.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c S Q',
            $this->rgb([0.09, 0.74, 0.51]),
            $x + 16,
            $y + 18,
            $x + 8,
            $y + 17,
            $x + 2,
            $y + 13,
            $x + 3,
            $y + 6,
            $x + 4,
            $y + 2,
            $x + 9,
            $y,
            $x + 12,
            $y + 3,
            $x + 16,
            $y + 7,
            $x + 17,
            $y + 12,
            $x + 16,
            $y + 18,
            $x + 5,
            $y + 3,
            $x + 8,
            $y + 8,
            $x + 11,
            $y + 11,
            $x + 15,
            $y + 13
        );
    }

    private function rgb(array $color): string
    {
        return sprintf('%.3F %.3F %.3F', $color[0], $color[1], $color[2]);
    }

    private function buildPdf(array $pages): string
    {
        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>\n",
            3 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n",
            4 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\n",
        ];
        $kids = [];
        $objectNumber = 5;

        foreach ($pages as $pageCommands) {
            $pageObject = $objectNumber++;
            $contentObject = $objectNumber++;
            $stream = implode("\n", $pageCommands)."\n";

            $kids[] = $pageObject.' 0 R';
            $objects[$pageObject] = sprintf(
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>\n",
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentObject
            );
            $objects[$contentObject] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream\n";
        }

        $objects[2] = "<< /Type /Pages /Kids [".implode(' ', $kids).'] /Count '.count($kids)." >>\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."endobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf
            ."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n"
            ."startxref\n".$xrefOffset."\n%%EOF";
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = Str::ascii($text);

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text) ?? $text;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalizeText($text));
    }

    private function shortenText(string $text, int $maxLength): string
    {
        $text = $this->normalizeText($text);

        return strlen($text) > $maxLength
            ? substr($text, 0, $maxLength - 3).'...'
            : $text;
    }

    private function safeFilename(string $filename): string
    {
        $filename = Str::ascii($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?? $filename;

        return str_ends_with(strtolower($filename), '.pdf') ? $filename : $filename.'.pdf';
    }
}
