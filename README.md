XenForo-ThreadReplyBanner
======================

Displayed per-thread banners above the editor for users.

- In both Quick Reply, and preview reply views.
- Only fetches the thread reply banner when viewing the thread.
- Text may be up to 65536 characters long, and supports bbcode.
- Supports caching rendered bbcode.
- Logs modifications of these banners to the Moderator Logs.
 
New Permission to control who can manage and see reply banners.
- View Thread Reply Banner - default - to users/groups who can reply.
- Manage Thread Reply Banner - default - to users/groups who can delete/edit all posts, or warn.

