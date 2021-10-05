<?php
declare(strict_types = 1);

namespace IdeHelperExtra\Tools\Generator\Task;

use Cake\Core\Configure;
use Cake\View\View;
use IdeHelper\Generator\Directive\ExpectedArguments;
use IdeHelper\Generator\Directive\RegisterArgumentsSet;
use IdeHelper\Generator\Task\TaskInterface;
use RuntimeException;
use Tools\View\Helper\FormatHelper;

class FormatIconBootstrapTask implements TaskInterface {

	public const CLASS_FORMAT_HELPER = FormatHelper::class;
	public const SET_ICONS_BOOTSTRAP = 'bootstrapIcons';

	/**
	 * @var string
	 */
	protected $fontPath;

	/**
	 * @param string|null $fontPath
	 */
	public function __construct(?string $fontPath = null) {
		if ($fontPath === null) {
			$fontPath = (string)Configure::readOrFail('Format.fontPath');
		}
		if ($fontPath && !file_exists($fontPath)) {
			throw new RuntimeException('File not found: ' . $fontPath);
		}

		$this->fontPath = $fontPath;
	}

	/**
	 * @return \IdeHelper\Generator\Directive\BaseDirective[]
	 */
	public function collect(): array {
		$result = [];

		$icons = $this->collectIcons();
		$list = [];
		foreach ($icons as $icon) {
			$list[$icon] = '\'' . $icon . '\'';
		}

		ksort($list);

		$registerArgumentsSet = new RegisterArgumentsSet(static::SET_ICONS_BOOTSTRAP, $list);
		$result[$registerArgumentsSet->key()] = $registerArgumentsSet;

		$method = '\\' . static::CLASS_FORMAT_HELPER . '::icon()';
		$directive = new ExpectedArguments($method, 0, [$registerArgumentsSet]);
		$result[$directive->key()] = $directive;

		return $result;
	}

	/**
	 * Bootstrap Icons using .json file.
	 *
	 * Set your custom file path in your app.php:
	 * 'Format' => [
	 *     'fontPath' => ROOT . '/webroot/css/bootstrap/font/bootstrap-icons.json',
	 *
	 * @return string[]
	 */
	protected function collectIcons(): array {
		$helper = new FormatHelper(new View());
		$configured = $helper->getConfig('fontIcons');
		/** @var string[] $configured */
		$configured = array_keys($configured);

		$fontFile = $this->fontPath;
		$icons = [];
		if (!file_exists($fontFile)) {
			throw new RuntimeException('File not found: ' . $fontFile);
		}

		$content = file_get_contents($fontFile);
		if ($content === false) {
			throw new RuntimeException('Cannot read file: ' . $fontFile);
		}
		$array = json_decode($content, true);
		if (!$array) {
			throw new RuntimeException('Cannot parse JSON: ' . $fontFile);
		}

		/** @var string[] $icons */
		$icons = array_keys($array);

		$icons = array_merge($configured, $icons);
		sort($icons);

		return $icons;
	}

}
