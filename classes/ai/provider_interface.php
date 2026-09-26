<?php
/**
 * AI Provider Interface for Knowledge Battle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    public function generate_quiz(string $context, int $count, string $difficulty = 'medium', string $language = 'pt-BR'): array;

    /**
     * Tests the connection to the AI API.
     *
     * @return bool True if connection is successful.
     */
    public function test_connection(): bool;

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
