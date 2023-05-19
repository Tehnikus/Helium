# Helium Store
Helium store is a deep modification of Opencart 3 with multiple major changes
It introduces comprehensive changes in frontend to have maximum performace, match all modern SEO requirements, support assistive technologies and screen readers, yet use minimum network payload.
TLDR:
- No side JS libraries, minimal code initialisation time, everything is written in Vanilla JS (under 20kb)
- No enormous CSS frameworks, all styles are handwritten (under 10kb)
- Optimized SQL structure and queries
- Caching of static data 
- Modern image formats - WEBP, SVG where needed
- All SEO requirements fulfilled
- Microdata markup in JSON-LD for Google rich snippets on all pages
- FAST loading time, under 100 milliseconds for heaviest page
- Comprehensive all-around support of assistive technologies for disabled users
- SEO Filter with static pages meta-tags, texts, content and so on
- Smart search with ranking, search words highlighting, images, countdown, product flags
- 100/100 Pagespeed, 100/100 SEO, 100/100 Accesibility

Requirements:
PHP7.4 =< PHP8.2
MySQL =< 8.0 or MariaDB =< 10.4
nGinx =< 1.23 
Redis (to be implemented)

## Search engine optimization
SEO Depends on multiple factors, both internal and external. If you are going to take higher place, first thing you do - is to make your pages bulletproof optimized on server side. This includes various code optimizations like remove content clones, hide service data and links like cart and account, comply SEO requirement related to tags, image formats, responsive design and so on. It can be quite challanging, if you want keep appearence and functional features.
I have an experience of optimizing sites on competetive markets, had multiple succefull projects. So here are part of my server side optimizations, I keep in mind while developing Helium Store:
### Do not hide content
If there is some content that is not displayed under any conditions, it is likely it will be reduced as ranking factor. So no content is hidden or any other way manipulated in Helium Store
### Place content that affect ranking in certain places
The higher the H1 headers and content in code - the better. 
For example, if your main content is way below initial load by Google bot (experimentally it is somewhere after 25kb of code), it will not see your content in first time scanning wich leads to slower indexing. 
So everything user- and Google bot- readable is placed as close to page start as possible. Place headings in order of importance. Make content attractive but not obstructive. Every requirement is covered in Helium store.
### Make content to code ratio in favor to content
If your page is overflown with code and scripts, it can be treated by Google bot as garbage page. So I used as little HTML markup as possible reaching the point when there is more user readable content, than markup itself. And also removed any inline scripts and styles, so no garbage code anywhere.
### Use semantic markup
Helium store is built on modern semantics, that distinct to Search engines the content, the navigation, the service content and so on.
### Make user interaction convenient
When user interacts with the page that signals to Search engines that viewed page is valuable. So interaction should be smooth and convenient. Helium store has no interaction delay, everything is accesible and on the spot. The interface was made with customers in mind to guide them from first page visit to purchase smoothly. 
### Keep all ranking factors filled.
There are multitudes of them, so I'll just mention that all tags are filled in if user didn't in admin part. You have all controls on every significant tag as:
- title
- decription
- H1-H3 headers inside page markup
- Texts appearence
- ect.
### Remove content cloning
There are couple of challanges related to content cloning. Let's take a look for example on category page and what is done in terms of texts optimization:
- Remove description texts on ordering and filtering pages (except static filters!), pagination, any non-canonical case
- Set titles and H1 according to filter, order and pagination
- Add 'noindex' tag to pages that are partially repeat content of canonical page itself
And so on. So every requirement is fulfilled here
### Create as much static pages with content as you can
Here you'll have specially developed instrumet - SEO Filter, where you can create static pages by filtering parameters.
Filter pages work exactly as category pages: it had same structure, same SEO tags, same markup with editable text and headers. Thus you can create multiple landing pages for different Google search requests and gain more visitors. Also filter and category pages have crosslinking: they refer to each other with convenient user-readable button-styled links, which both contributes to user experience and SEO.
Filter pages are based on same architecture as category pages, so they deliver content as fast as category pages and have the same functionality.
### Preload, lazy load, preconnect
There are several types of content that should be preloaded: scripts, styles, images that appear inside of first viewport.
There is content that should be lazy loaded: images outside of viewport, unneccesary scripts, fonts.
I've covered every aspect of async preloading to make Helium Store fast and futureproof. 
### Make your site FAST
Also search bots have time quota for every domain, so if pages load exceedes 500 milliseconds, your website will be scanned 50-100 pages at the time. No such issue with Helium Store, because it loads every page for about 100 milliseconds. Also BestBuy reseach shows that faster loading pages increases conversion rate. In Helium store pages load almost instantly, which leads to better user experience.

## Images
Helium Store uses WEBP and SVG formats by default. The dimensions are lined up in such way to avoid multiple sizes of same image and fit responsive design on various screens at the same time.
But those are completely editable. 
SVG support implemented with proper resizing, so you can use SVG for product options or favicon, elsewhere.

## Database
Every single DB query in Helium store was tested to be as fast as possible to support multiple simultaneous clients browsing store with thouthands of products.
The DB structure changes include new primary keys, indexes, optimized queries and minimization of traffic between SQL and PHP processes.
Slow queries as product search, filtering and displayand others were rewritten from scratch.
Several tables were added for new functions.

## Javascript
To the opposit of common usage of side libraries Helium Store relies on Vanilla JS written from the scratch.
The goal is to create lightweight and fast initializing code that uses native browser features.
Here are some of features included in this project
### Main menu
1. Responsive and convenient on mobile devices and desktops as well. 
2. Has assistive tehnologies markup for diasbled users, announces navigation points, supports keyboard interaction, hides closed menu blocks from screen readers. 
3. Best SEO results - does't hide markup from Google bot as many others.
4. Does not affect time-to-interactive metric.
5. All the data gathered from DB in single optimized query.
6. Uses some neat animations
### Live Search
1. Displays products with images, prices, short descriptions. 
2. Highlights search terms in text. 
3. Shows countdown to discount end date if applicable. 
4. Orders products by relevance to the search term.
5. Has support for assistive technologies and keyboard interaction.
6. Works really fast! Renders products on client side so does not load server with unneccessary requests
In future updates I will add search in Blog posts, Product filter pages, Categories, Product features to search results to make search more convenient and user-engaging
### Live cart
The main part of every online store is checkout process. So I focused to make it as easy as possible.
1. The checkout dialog has quick order form, that creates order the same as regular checkout.
2. If customer adds product to cart from category, featured or other product list AND product has required options, the dialog appears with selection product options
3. Live price calculation with neat animation. Price changes accordingly to:
   3.1 Product option that impacts product price (i.e. volume).
   3.2 Batch discounts, like "buy 2 and get 25% off".
   Also uses requestAnimationFrame, so animations do not cause any slowdowns or interactivity decline.
4. Every checkout field and option has support for assistive technologies with announcing errors if occured.
5. Cart does not expose any links or content to Google bot so does not interfrer with SEO results.
6. Shows products number on favicon in browser (Facebook-like message counter) so the customer will not forget to finish checkut.
### Sliders
As for me, sliders are kind of antipattern in modern design, but sometimes they are the only way to introduce product, attract attention and engage users, and also sqeeze a lot of products in relatively small space. So I created really neat slider, that complies SEO, does not hide content, responsive without media queries (yeah, that's possible), relies on browser native features, supports touch and inertial scrolling and initializes for couple of milliseconds.
Used to display product lists like:
- Viewed products
- Featured products
- Discounts
- Latest reviews (under development)
- Related articles from blog
- Related products to other products or blog articles
- Any HTML content (also under development)
- etc.
The biggest point here as Sliders support native browser touch interactions, so no akward behaviour on touch devices. Also perfectly support for keyboard interactions and, consequently, assistive technologies.
### Load more
Load more button fetches products in product lists, articles, comments on products and articles. Works fast, all events (interactivity) attached properly.
### Countdown
Shows countdown to discount end, if the date is set in Admin backoffice. Encourages users for impulsive purchases. Displayed where related product appear: category, live search, related, viewed featured products, product page itself, etc. Has assistive technologies tags, so screen readers will not announce every timer change.
### Anchor nav
It allows to focus user input on certain page blocks, skipping others. Smoothly scrolls to target and shows focus state.
This helps users with assistive technologies and also keeps Google on the page for a bit longer.
Used on category pages, products, blog posts.
If the page has other ranking factors fulfilled, those links will appear on Search Engine Page Results (SERP) as page contents.

## CSS
All the styles were writteh from the scratch, on top of Normalize CSS. It is responsive and also doesn't manipulate content visibility not to harm SEO. 
Minimum mediaqueries used, basically for narrow and wide screens to ommit useless grids for number of screen sizes.
No monstrous CSS frameworks used, as realworld pages use 10%-20% of that code.
### Color system
Design relies heavily on CSS variables and built-in CSS calc() functions.
Calculations are used to dynamically create color schema by using HSL colors.
This means you can quickly change primary color and all other complementary and triad colors will be calculated by CSS.
This also relates to focus states, call-to-action buttons, color accents.
Transparency, animations, shadows and rounded corners also controlled in one place, so no need to search and replace multiple lines.
Every bit of design changes accordingly to global variables.
Currently minified and gzipped CSS file is under 10kb of payload.

## Features
There are multiple features I made in this project, that distinc it from other standart online stores. I'll just review several:
### Category and Filter cross-linking
Filter pages and Categories have cross links, that help users to navigate and also is a powerful feature for SEO. This means you will get more pages in Google index, thus better overall results and more customers.
### Product ordering
If you think like a customer, how do you pick product from list of similar ones? Here is the answer: sort them in convenient way. Here are some sorting options, that I implemented in Helium Store in addition to standart dummy A-Z:
- Best sellers (with returns accounted also)
- Best reviews (counting both overall rating and reviews quantity)
- New arrivals
- Discounted products first
- Last updated
- Best value by price/weight (if you sell volume products, your customers will love it!)
... and 14 more sorting ways
### Product flags (or badges)
Highlight products among others with flags. This feature shows badges on products in every list, where they appear. There are three types of flags: static, dynamic and service. 
Static flags show some product features:
- Special price
- Batch discounts (for example: buy 2 with 10% off)
- Hot sale (if there is discout date end present)
- Video (if video is present in description)
- Featured
Dynamic flags kind of similar to product sorting, they appear on number of first products that suit requirements:
- Bestseller (for every category)
- Best reviews (for every category)
If product appear in some other page than category, product's main category is used as context of dynamic flag
Service flags just show user inderactions:
- Viewed product
- Added to wishlist
- Added to compare
- Already bought by current customer
Nice and convenient way to showcase products and drive more conversions.

## Summary
I cannot cover every feature here, I developed in Helium Store. I'll just add, that I use it myself :)
