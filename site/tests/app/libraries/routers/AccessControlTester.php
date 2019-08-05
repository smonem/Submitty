<?php


namespace tests\app\libraries\routers;

use tests\BaseUnitTest;
use app\models\User;
use app\libraries\routers\WebRouter;
use Symfony\Component\HttpFoundation\Request;


class AccessControlTester extends BaseUnitTest {
    private $semester = 'test_semester';

    private $course = 'test_course';

    public function data() {
        $course_prefix = '/' . $this->semester . '/' . $this->course;
        return [
            [$course_prefix . '/gradeable/open_homework/update', "GET", [], User::GROUP_INSTRUCTOR],
        ];
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $params
     * @param int $min_role
     * @param array $min_permission
     * @param bool $logged_in
     *
     * @dataProvider data
     */
    public function testAccess(
        $endpoint,
        $method="GET",
        $params=[],
        $min_role = User::LEVEL_USER,
        $min_permission = ['course.view'],
        $logged_in = true
    ) {
        for ($role = User::GROUP_STUDENT; $role > $min_role; $role --) {
            $core = $this->getAccessTestCore($role, $min_permission);
            $request = Request::create(
                $endpoint,
                $method,
                $params
            );

            $response = WebRouter::getWebResponse($request, $core, $logged_in);

            $this->assertEquals(
                [
                    'status' => 'fail',
                    'message' => "You don't have access to this endpoint."
                ],
                $response->json_response->json
            );
        }
    }

    private function getAccessTestCore($role, $permission) {
        switch ($role) {
            case User::GROUP_INSTRUCTOR:
                $core = $this->createMockCore(['access_admin' => true, 'semester' => $this->semester, 'course' => $this->course], [], [], $permission);
                break;
            case User::GROUP_FULL_ACCESS_GRADER:
                $core = $this->createMockCore(['access_full_grading' => true, 'semester' => $this->semester, 'course' => $this->course], [], [], $permission);
                break;
            case User::GROUP_LIMITED_ACCESS_GRADER:
                $core = $this->createMockCore(['access_grading' => true, 'semester' => $this->semester, 'course' => $this->course], [], [], $permission);
                break;
            case User::GROUP_STUDENT:
            default:
                $core = $this->createMockCore(['semester' => $this->semester, 'course' => $this->course], [], [], $permission);
                break;
        }
        return $core;
    }
}