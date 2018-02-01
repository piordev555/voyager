<?php

namespace TCG\Voyager\Tests;

use Illuminate\Support\Facades\Auth;

class DashboardTest extends TestCase
{
    protected $withDummy = true;

    public function setUp()
    {
        parent::setUp();

        $this->install();
    }

    public function testWeHaveAccessToTheMainSections()
    {
        // We must first login and visit the dashboard page.
        Auth::loginUsingId(1);

        $this->visit(route('voyager.dashboard'));

        $this->see(__('voyager::voyager.generic.dashboard'));

        // We can see number of Users.
        $this->see(trans_choice('voyager::voyager.dimmer.user', 1));

        // list them.
        $this->click(__('voyager::voyager.dimmer.user_link_text'));
        $this->seePageIs(route('voyager.users.index'));

        // and return to dashboard from there.
        $this->click(__('voyager::voyager.generic.dashboard'));
        $this->seePageIs(route('voyager.dashboard'));

        // We can see number of posts.
        $this->see(trans_choice('voyager::voyager.dimmer.post', 4));

        // list them.
        $this->click(__('voyager::voyager.dimmer.post_link_text'));
        $this->seePageIs(route('voyager.posts.index'));

        // and return to dashboard from there.
        $this->click(__('voyager::voyager.generic.dashboard'));
        $this->seePageIs(route('voyager.dashboard'));

        // We can see number of Pages.
        $this->see(trans_choice('voyager::voyager.dimmer.page', 1));

        // list them.
        $this->click(__('voyager::voyager.dimmer.page_link_text'));
        $this->seePageIs(route('voyager.pages.index'));

        // and return to Dashboard from there.
        $this->click(__('voyager::voyager.generic.dashboard'));
        $this->seePageIs(route('voyager.dashboard'));
        $this->see(__('voyager::voyager.generic.dashboard'));
    }
}
