<?php

/** @noinspection PhpUnusedLocalVariableInspection */
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\MultipleAdvicesWithSameAdviceTypeOnSameTargetMethod;

use Exception;
use Okapi\Aop\Tests\ClassLoaderMockTrait;
use Okapi\Aop\Tests\Functional\AdviceBehavior\MultipleAdvicesWithSameAdviceTypeOnSameTargetMethod\Aspect\ProfilePictureValidatorAspect;
use Okapi\Aop\Tests\Functional\AdviceBehavior\MultipleAdvicesWithSameAdviceTypeOnSameTargetMethod\Target\ProfileController;
use Okapi\Aop\Tests\Util;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class MultipleAdvicesWithSameAdviceTypeOnSameTargetMethodTest extends TestCase
{
    use ClassLoaderMockTrait;

    public const AVATAR_PATH = __DIR__ . '/../../../media/avatar.png';
    public const AVATAR_HQ_PATH = __DIR__ . '/../../../media/avatar-HQ.png';
    public const AVATAR_WRONG_FORMAT_PATH = __DIR__ . '/../../../media/avatar-wrong-format.jpg';

    /**
     * @see ProfilePictureValidatorAspect::checkImageSize()
     * @see ProfilePictureValidatorAspect::checkImageFormat()
     */
    public function testMultipleBeforeAdvicesOnSameTargetMethod(): void
    {
        Util::clearCache();
        Kernel::init();

        $this->assertWillBeWoven(ProfileController::class);
        $profileController = new ProfileController();

        // Valid avatar
        $path = $profileController->uploadProfilePicture('avatar', self::AVATAR_PATH);

        // No exception thrown
        static::assertTrue(true);

        static::assertSame('https://example.com/avatar', $path);

        // Invalid avatar size
        $exceptionThrown = false;
        try {
            $profileController->uploadProfilePicture('avatar', self::AVATAR_HQ_PATH);
        } catch (Exception $e) {
            $exceptionThrown = true;
            static::assertSame('Image is too big', $e->getMessage());
        }
        static::assertTrue($exceptionThrown);

        // Invalid avatar format
        $exceptionThrown = false;
        try {
            $profileController->uploadProfilePicture('avatar', self::AVATAR_WRONG_FORMAT_PATH);
        } catch (Exception $e) {
            $exceptionThrown = true;
            static::assertSame('Invalid image format', $e->getMessage());
        }
        static::assertTrue($exceptionThrown);
    }
}
