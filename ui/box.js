jQuery(document).ready(function($) {

// Placeholder support for IE
if (!jQuery('<input placeholder="1" />')[0].placeholder) {
	jQuery('.p2p-search input[placeholder]').each(function(){
		var $this = $(this);
		if (!$this.val()) {
			$this.val($this.attr('placeholder'));
			$this.addClass('p2p-placeholder');
		}
	}).focus(function(e){
		var $this = $(this);
		if ($this.hasClass('p2p-placeholder')) {
			$this.val('');
			$this.removeClass('p2p-placeholder');
		}
	}).blur(function(e){
		var $this = $(this);
		if (!$this.val()) {
			$this.addClass('p2p-placeholder');
			$this.val($this.attr('placeholder'));
		}
	});
}

$('.p2p-create-connections').each(function() {
	var
		$addNew = $(this),
		$metabox = $addNew.closest('.inside'),
		$connections = $metabox.find('.p2p-connections'),
		$searchInput = $metabox.find('.p2p-search :text'),
		$pagination = $metabox.find('.p2p-nav'),
		base_data = {
			box_id: $addNew.attr('data-box_id'),
			direction: $addNew.attr('data-direction')
		},
		$spinner = $('#publishing-action .ajax-loading')
			.clone()
			.removeAttr('id')
			.removeClass('ajax-loading')
			.addClass('waiting');

	// Delete all connections
	$metabox.delegate('th.p2p-col-delete a', 'click', function() {
		if ( !confirm(P2PAdmin_I18n.deleteConfirmMessage) )
			return false;

		var $self = $(this),
			$td = $self.closest('td'),
			data = $.extend( base_data, {
				action: 'p2p_connections',
				subaction: 'clear_connections',
				post_id: $('#post_ID').val()
			} );

		$td.html( $spinner.show() );

		$.post(ajaxurl, data, function(response) {
			$connections.hide()
				.find('tbody').html('');

			$td.html( $self );
		});

		return false;
	});

	// Delete connection
	$metabox.delegate('td.p2p-col-delete a', 'click', function() {
		var
			$self = $(this),
			$td = $self.closest('td'),
			data = $.extend( base_data, {
				action: 'p2p_connections',
				subaction: 'disconnect',
				p2p_id: $self.attr('data-p2p_id')
			} );

		$td.html( $spinner.show() );

		$.post(ajaxurl, data, function(response) {
			$td.closest('tr').remove();

			if ( !$connections.find('tbody tr').length )
				$connections.hide();
		});

		return false;
	});

	// Create connection
	$metabox.delegate('td.p2p-col-create a', 'click', function() {
		var
			$self = $(this),
			$td = $self.closest('td'),
			data = $.extend( base_data, {
				action: 'p2p_connections',
				subaction: 'connect',
				from: $('#post_ID').val(),
				to: $self.attr('data-post_id')
			} );

		$td.html( $spinner.show() );

		$.post(ajaxurl, data, function(response) {
			$connections.show()
				.find('tbody').append(response);

			if ( $addNew.attr('data-prevent_duplicates') ) {
				$td.closest('tr').remove();

				if ( !$results.find('tbody tr').length )
					$results.hide();
			} else {
				$td.html( $self );
			}
		});

		return false;
	});

	// Tabs
	$metabox.delegate('.wp-tab-bar li', 'click', function() {
		var $tab = $(this);

		// Set active tab
		$metabox.find('.wp-tab-bar li').removeClass('wp-tab-active');
		$tab.addClass('wp-tab-active');

		// Set active panel
		$metabox.find('.tabs-panel').hide();
		$metabox.find( $tab.attr('data-ref') )
			.show()
			.find(':text').focus();

		return false;
	});

	// Pagination
	var current_page = 1, total_pages = 0;

	function find_posts(new_page, action, callback) {
		var data = $.extend( base_data, {
			action: 'p2p_search',
			s: $searchInput.val(),
			paged: new_page,
			post_id: $('#post_ID').val()
		} );

		new_page = new_page ? ( new_page > total_pages ? current_page : new_page ) : current_page;

		$spinner.insertAfter( $searchInput ).show();

		$.getJSON(ajaxurl, data, function(response) {
			$spinner.remove();
			current_page = new_page;

			$metabox.find('.p2p-results, .p2p-navigation').remove();
			$metabox.find('.p2p-search').find('.p2p-notice').remove();

			if ( 'undefined' === typeof response.rows ) {
				$metabox.find('.p2p-search').append('<p class="p2p-notice">' + response.msg + '</p>');
			} else {
				$searchInput.after(response.rows);

				total_pages = response.pages;

				if ( undefined !== callback )
					callback();
			}
		});
	}

	// Post creation
	$metabox.delegate('.button', 'click', function() {
		var $button = $(this);

		if ( $button.hasClass('inactive') )
			return false;

		var title = $metabox.find('.p2p-create-post :text').val();
		if ( '' === title ) {
			$metabox.find('.p2p-create-post :text').focus();
			return false;
		}

		$button.addClass('inactive');

		var data = $.extend( base_data, {
			action: 'p2p_connections',
			subaction: 'create_post',
			from: $('#post_ID').val(),
			post_title: title
		});

		$.post(ajaxurl, data, function(response) {
			$connections.show()
				.find('tbody').append(response);

			$metabox.find('.p2p-create-post :text').val('');
			$metabox.find('.p2p-topost-adder-toggle').click();
			$button.removeClass('inactive');
		});

		return false;
	});

	// Search posts
	var delayed, old_value = '';

	$metabox.find('.p2p-search :text')
		.keypress(function (ev) {
			if ( 13 === ev.keyCode )
				return false;
		})
		.keyup(function (ev) {
			if ( undefined !== delayed ) {
				clearTimeout(delayed);
			}

			var $self = $(this);

			delayed = setTimeout(function() {

				if ( $self.val() === old_value ) {
					return;
				}
				old_value = $self.val();

				find_posts(1, 'search');

			}, 400);
		});

	// Pagination
	$metabox.delegate('.p2p-prev, .p2p-next', 'click', function() {
		var $self = $(this),
			new_page = current_page;

		if ( $self.hasClass('inactive') )
			return false;

		if ( $self.hasClass('p2p-prev') )
			new_page--;
		else
			new_page++;

		find_posts(new_page, 'paginate');

		return false;
	});
});
});

