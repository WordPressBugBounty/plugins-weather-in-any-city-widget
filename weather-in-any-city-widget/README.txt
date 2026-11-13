=== Weather Widget Pro – Weather Forecast Widget, Block & Shortcode (No API Key) ===
Contributors: hullcode
Tags: weather, weather widget, weather forecast, tiempo, clima
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 5.6
Stable tag: 2.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free weather widget, block and shortcode: current conditions and a 14-day forecast for 200,000 cities. No API key, no sign-up.

== Description ==

**Weather Widget Pro** shows the current weather and a forecast of up to 14 days for any of 200,000 cities, anywhere on your site. It is free, there is no paid version, and it works out of the box: **no API key, no account, nothing to configure**.

Pick a city with the built-in search, choose what to show, and you are done.

= What you get =

* **Sky card design (new in 2.0)**: a card whose sky changes with the weather. Rain falls, snow drifts, stars come out at night, and dawn and dusk have their own light. Vertical for sidebars or horizontal for wide spaces: the horizontal card stacks automatically where space is tight.
* **47 weather icons**: five degrees of cloudiness, three intensities of rain and snow, drizzle, sleet, storms, fog, haze and more, by day and by night.
* **Current conditions**: temperature, the weather in words, feels-like temperature, humidity, wind and gusts, pressure, cloudiness, visibility, sunrise and sunset.
* **Hourly forecast** and a **daily forecast for up to 14 days**: high and low temperature, chance of rain, rainfall and wind.
* **200,000 cities worldwide**, found with a search box as you type.
* **No API key and no registration.** The weather service is provided free of charge by the plugin authors.
* **Three ways to add it**: a block for the block editor, a classic widget for sidebars and footers, and the `[weather_pro]` shortcode for any page, post or page builder.
* **Celsius or Fahrenheit**, km/h or mph, 24-hour or 12-hour clock. Optionally, let your visitors switch units themselves.
* **The classic design is still here**: horizontal or vertical layout, your own background, text and border colors, font size, color or single-color icons.
* **Show or hide every element**, one by one.
* **Light on your site**: one small script without jQuery, weather data kept on your site for 30 minutes and written into the page, and the last good data shown if the weather service cannot be reached.
* **Responsive** and compatible with any theme, classic or block-based. Works on multisite.
* **English and Spanish**, translation-ready.

= Quick start =

**Block editor (any theme)**

1. Edit a page, post or template and click **+**.
2. Search for **Weather Widget Pro** and insert the block.
3. Type your city, pick it from the list, and choose the options you want in the block sidebar.

**Classic widget (classic themes)**

1. Go to **Appearance > Widgets**.
2. Add **Weather Widget Pro** to a sidebar or footer.
3. Type your city, pick it from the list, adjust the options and save.

**Shortcode (anywhere, including page builders)**

1. Go to **Settings > Weather Widget Pro**.
2. Pick your city and options, and click **Copy**.
3. Paste it into any page, post, text widget or shortcode element.

A minimal shortcode looks like this:

`[weather_pro cityid="86168" city="New York City" design="card" units="f" days="5"]`

= Shortcode attributes =

* `cityid` – the city (use the generator in Settings > Weather Widget Pro to find it)
* `city`, `title` – the name shown at the top
* `design` – `card` (the sky card) or `classic`; shortcodes without it keep the classic design
* `units` – `c` (metric) or `f` (imperial)
* `days` – `1` to `14`, or `off` to hide the forecast
* `today`, `now_icon`, `now_temp`, `now_humidity`, `now_wind`, `now_pressure`, `now_cloudiness`, `now_sunrise` – `on` / `off`
* `temp`, `icon`, `rainchance`, `rain`, `wind` – forecast columns, `on` / `off`
* `time_format` – `universal` (24 h) or `standard` (12 h)
* `fontsize` – in pixels
* Sky card only: `description`, `feels_like`, `now_visibility`, `hourly`, `unit_switch`, `updated`, `animation` – `on` / `off`
* `layout` – `horizontal` (default) or `vertical`; a horizontal sky card stacks automatically in narrow spaces
* Classic design only: `backgroundcolor`, `textcolor`, `bordercolor`, `weathericonscolor` (`color`, `dark` or `light`), `iconscolor` (`dark` or `light`), `shadow` (`on` / `off`)

== Installation ==

1. In your dashboard, go to **Plugins > Add New**, search for **Weather Widget Pro** and click **Install Now**, then **Activate**.
2. Follow the **Quick start** above: add the block, the classic widget or the shortcode. **Settings > Weather Widget Pro** explains the three.

No API key or account is needed.

== Frequently Asked Questions ==

= I activated the plugin. Where are the settings? =

The settings belong to each weather widget, so they appear where you add it. In the block editor, insert the **Weather Widget Pro** block and use the block sidebar. In a classic theme, go to **Appearance > Widgets**. To build a shortcode, go to **Settings > Weather Widget Pro**.

= My theme has no "Appearance > Widgets" menu. What do I do? =

Block themes (such as Twenty Twenty-Two and the default themes after it) do not have widget areas. Use the **Weather Widget Pro** block in the Site Editor or in any page, or paste the shortcode into a Shortcode block.

= Do I need an API key or an account? =

No. The plugin gets its data from a weather service run by the plugin authors (eltiempoen.com and weatherin.org) and it is free to use. See "External services" below for what is sent.

= I updated from an older version. Will my widgets change? =

No. Widgets and shortcodes you already have keep the classic design and their settings. The sky card is the default only for the widgets and blocks you add from now on; to switch an existing widget, choose **Design > Sky card** in its settings, or add `design="card"` to its shortcode.

= The widget shows New York instead of my city. Why? =

The city must be picked from the suggestions that appear while you type, not just typed in. Start typing, click your city in the list, then save. If no suggestions appear, check that your browser or a security plugin is not blocking requests to eltiempoen.com.

= Can I show only the temperature, or only the current weather? =

Yes. Every element can be switched off. For the current weather alone, hide the forecast with `days="off"`.

= Can I show several cities on the same page? =

Yes. Add as many blocks, widgets or shortcodes as you like, each with its own city and design.

= How often is the weather updated? Will it slow down my site? =

Your site asks the service about once every 30 minutes per city and keeps the answer (after a failed request it tries again a few minutes later). When it has it, the data is written into the page and the widget needs no extra request. If the service cannot be reached, the last good data is shown.

= Is it translated into my language? =

It comes in English and Spanish. You can help with other languages at translate.wordpress.org.

== External services ==

This plugin connects to the weather service at https://eltiempoen.com/api/weather, run by the plugin authors (eltiempoen.com and weatherin.org) and delivered through Cloudflare. It is needed to search for cities and to get the weather; without it the plugin shows nothing.

* **When you search for a city in the settings** (widget, block or shortcode generator), your browser sends the text you type to the service. As with any request, the service sees your IP address and the address of the admin page you are on.
* **When a page with a weather widget is viewed**, your site's server asks for the weather, normally once every 30 minutes per city. It sends the ID of the city, the address of your site and the version of the plugin. As with any request, the service sees the IP address of your server.

Your visitors' browsers never contact the weather service: everything goes through your site's server.

The plugin sets no cookies and sends no data about your visitors. If you let visitors switch between °C and °F, their choice is saved in their own browser (local storage) and is never sent anywhere. To keep the service free and prevent abuse, the service keeps the address of each site that uses it, its plugin version and how many requests it makes.

Privacy policy: https://weatherin.org/privacy-policy (English) and https://eltiempoen.com/politica-de-privacidad (Spanish). Legal notice: https://weatherin.org/legal-notice

== Screenshots ==

1. The sky card: its sky changes with the weather. Sun, rain and a clear night.
2. The horizontal sky card, for wide spaces: current weather, the next few hours and the days ahead.
3. The Weather Widget Pro block in the editor, with the city search and its settings.
4. Settings > Weather Widget Pro writes the shortcode for you.
5. The classic design, with your own colors, next to the sky card.

== Changelog ==

= 2.0.2 =
* Fixed: in Appearance > Widgets (block-based screen), the design and the new options of a widget were not saved.
* Fixed: on PHP 8, the form of a widget with the forecast disabled showed "14 days" selected.
* Fixed: the sky card no longer overflows narrow grid or flex columns, and the horizontal card no longer cuts the daily high.
* Improved: sky cards placed one after another keep a space between them.
* Improved: pages kept in a cache from before 2.0 show the last good data when the weather service cannot be reached.
* Changed: the widget form no longer shows a shortcode; Settings > Weather Widget Pro writes it for you.

= 2.0.1 =
* Fixed: the preview of the block stayed empty in the editor of the latest WordPress versions.

= 2.0.0 =
* New: the sky card design. Its sky changes with the weather (rain, snow, clouds, stars, dawn and dusk), with 47 weather icons, vertical or horizontal.
* New: the weather in words, feels-like temperature, gusts, visibility and an hourly forecast for the next few hours.
* New: visitors can switch between °C and °F (optional).
* New: a block for the block editor, with the city search in its sidebar (WordPress 5.3 or later).
* New: Settings > Weather Widget Pro explains how to add the weather and writes the shortcode for you.
* Improved: when the weather service cannot be reached, the widget shows the last good data instead of staying empty.
* Improved: the visitor's browser no longer contacts the weather service; only your site's server does.
* Improved: fewer requests. The data your site already has is written into the page, and widgets of the same city share one request.
* Improved: the styles load in the head of the page, so the widget no longer jumps when it appears.
* Improved: texts for screen readers follow your site language.
* Fixed: notices on PHP 8 when saving a widget, and `units="C"` showing Fahrenheit.
* Existing widgets and shortcodes keep their design and settings.

= 1.1.42 =
* Fixed: the city search of the widget settings works again (the service moved to the standard HTTPS port).
* Fixed: when the site cannot get the weather, the visitor's browser now asks the service directly, also after an HTTP error.
* Fixed: a shortcode without attributes no longer fails on PHP 8.
* Improved: the connection to the service is verified (SSL), and a failed request is not repeated for a minute.

== Upgrade Notice ==

= 2.0.0 =
A major update: new sky card design, a block for the block editor and an hourly forecast. Your existing widgets and shortcodes stay as they are.
