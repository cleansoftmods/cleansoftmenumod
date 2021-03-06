<?php namespace WebEd\Base\Menu\Support;

use Illuminate\Support\Collection;
use WebEd\Base\Users\Models\User;

class DashboardMenu
{
    /**
     * Get all registered links from package
     * @var array
     */
    protected $links = [];

    /**
     * Get all activated items
     * @var array
     */
    protected $active = [];

    /**
     * @var User
     */
    protected $loggedInUser;

    /**
     * @var string
     */
    protected $builtHtml;

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->loggedInUser = $user;
    }

    /**
     * Add link
     * @param array $options
     * @return $this
     */
    public function registerItem(array $options)
    {
        if (isset($options['children'])) {
            unset($options['children']);
        }
        $defaultOptions = [
            'id' => null,
            'priority' => 99,
            'parent_id' => null,
            'heading' => null,
            'title' => null,
            'font_icon' => null,
            'link' => null,
            'css-class' => null,
            'children' => [],
            'permissions' => [],
        ];
        $options = array_merge($defaultOptions, $options);
        $id = $options['id'];

        if (!$id) {
            $calledClass = debug_backtrace()[2];
            throw new \RuntimeException('Menu id not specified: ' . $calledClass['class'] . '@' . $calledClass['function']);
        }
        if (isset($this->links[$id])) {
            $calledClass = debug_backtrace()[2];
            throw new \RuntimeException('Menu id already exists: ' . $id . ' on class ' . $calledClass['class'] . '@' . $calledClass['function']);
        }
        $parentId = $options['parent_id'];
        if ($parentId && !isset($this->links[$parentId])) {
            $calledClass = debug_backtrace()[2];
            throw new \RuntimeException('Parent id not exists: ' . $id . ' on class ' . $calledClass['class'] . '@' . $calledClass['function']);
        }

        $this->links[$id] = $options;

        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function removeItem($id)
    {
        array_forget($this->links, $id);

        return $this;
    }

    /**
     * Rearrange links
     * @return Collection
     */
    protected function rearrangeLinks()
    {
        $links = $this->getChildren();
        $links = collect($links)->sortBy('priority');
        return $links;
    }

    /**
     * Get children items
     * @param null $id
     * @return Collection
     */
    protected function getChildren($id = null)
    {
        $children = collect([]);
        foreach ($this->links as $key => $row) {
            if ($row['parent_id'] == $id) {
                $row['children'] = $this->getChildren($row['id']);
                $children->push($row);
            }
        }
        return $children->sortBy('priority');
    }

    /**
     * Get activated items
     * @param $active
     */
    protected function setActiveItems($active)
    {
        foreach ($this->links as $key => $row) {
            if ($row['id'] == $active) {
                $this->active[] = $active;
                $this->setActiveItems($row['parent_id']);
            }
        }
    }

    /**
     * Render the menu
     * @param null|string $active
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function setActiveItem($active = null)
    {
        $this->setActiveItems($active);

        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        $links = $this->rearrangeLinks();
        return view('webed-menu::admin.dashboard-menu.menu', [
            'isChildren' => false,
            'links' => $links,
            'level' => 0,
            'active' => $this->active,
            'loggedInUser' => $this->loggedInUser
        ])->render();
    }
}
