<?php
/**
 * Unit tests for AI Provider Layer.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use advanced_testcase;
use mod_knowledgebattle\ai\provider_factory;
use mod_knowledgebattle\ai\provider_interface;
use mod_knowledgebattle\ai\openai_provider;
use mod_knowledgebattle\ai\gemini_provider;
use mod_knowledgebattle\ai\claude_provider;
use mod_knowledgebattle\ai\deepseek_provider;
use mod_knowledgebattle\ai\groq_provider;
use mod_knowledgebattle\ai\openrouter_provider;
use mod_knowledgebattle\ai\local_llm_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for AI provider layer.
 *
 * @covers \mod_knowledgebattle\ai\provider_factory
 */
class ai_provider_test extends advanced_testcase {

    public function test_provider_factory_instantiates_all_providers() {
        $providers = [
            'openai'     => openai_provider::class,
            'gemini'     => gemini_provider::class,
            'claude'     => claude_provider::class,
            'deepseek'   => deepseek_provider::class,
            'groq'       => groq_provider::class,
            'openrouter' => openrouter_provider::class,
            'local_llm'  => local_llm_provider::class,
        ];

        foreach ($providers as $name => $expected_class) {
            $instance = provider_factory::create($name, 'test-key', 'test-model');
            $this->assertInstanceOf(provider_interface::class, $instance);
            $this->assertInstanceOf($expected_class, $instance);
            $this->assertNotEmpty($instance->get_provider_name());
        }
    }

    public function test_provider_factory_invalid_provider_throws() {
        $this->expectException(\moodle_exception::class);
        provider_factory::create('invalid_non_existent_provider', 'key', 'model');
    }

    public function test_supported_models_list() {
        $gemini = provider_factory::create('gemini', 'key', 'gemini-2.5-flash');
        $models = $gemini->get_supported_models();
        $this->assertIsArray($models);
        $this->assertContains('gemini-2.5-flash', $models);
    }
}
