<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * AI Provider Interface for Knowledge Battle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface provider_interface
 * @package mod_knowledgebattle\ai
 */
interface provider_interface {
    /**
     * Generates a quiz from the provided context.
     *
     * @param string $context The text content to generate questions from.
     * @param int $count Number of questions to generate.
     * @param string $difficulty Difficulty level (e.g., 'easy', 'medium', 'hard').
     * @param string $language Language for the generated questions.
     * @return array Array of question objects.
     */
    public function generate_quiz(string $context, int $count, string $difficulty = 'medium', string $language = 'English'): array;

    /**
     * Tests the connection to the AI API.
     *
     * @return bool True if connection is successful.
     */
    public function test_connection(): bool;

    /**
     * Tests the connection with detailed diagnostic information.
     *
     * @return array Array with keys: success, latency, provider, model, http_code, reply, error.
     */
    public function test_connection_detailed(): array;

    /**
     * Gets the display name of the provider.
     *
     * @return string
     */
    public function get_provider_name(): string;

    /**
     * Gets a list of supported models by this provider.
     *
     * @return array Array of model string identifiers.
     */
    public function get_supported_models(): array;
}
